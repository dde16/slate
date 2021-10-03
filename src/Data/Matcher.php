<?php

namespace Slate\Data {

    use Slate\Data\Iterator\ArrayAssocIterator;
    use Slate\Data\Iterator\ArrayExtendedIterator;
    use Slate\Data\Iterator\IAnchoredIterator;
    use Slate\Data\Iterator\IExtendedIterator;
    use Slate\Data\IteratorFactory;

    class Matcher {
        /** Match longest possible string of correct matches */
        public const PRECEDENCE_GREEDY  = (1<<0);

        /** Match smallest possible string of correct matches */
        public const PRECEDENCE_LAZY    = (1<<2); 

        public const STRICT             = (1<<0);
        public const SPREAD             = (1<<1);

        /**
         * @var int $precedence
         * 
         * Stores the precedence behaviour (how greedy each side of a back-to-back spreading match is).
         */
        protected int $precedence;

        /**
         * @var array $blueprint
         * 
         * The blueprint on how to match values.
         */
        protected object $blueprint;

        public function __construct(object $blueprint, int $precedence = Matcher::PRECEDENCE_GREEDY) {
            if(is_array($blueprint)) {
                $blueprint = new ArrayExtendedIterator($blueprint);
            }
            else if(!\Cls::implements($blueprint, [IExtendedIterator::class, IAnchoredIterator::class])) {
                throw new \Error("Blueprint Iterattor must implement the IExtendedIterator and IAnchoredIterator interfaces.");
            }

            $this->blueprint = $blueprint;
            $this->precedence = $precedence;
        }

        public function match(string|array $against): array {
            $againstType = \Any::getType($against, verbose: true);

            $againstIterator = IteratorFactory::create($againstType[1][0] ?: $againstType[0], [$against]);

            $eval = 1;

            $matches = [];

            $lastMatcher = null;
            $lastMatch   = null;

            while($againstIterator->valid() && $eval) {
                if($this->blueprint->valid()) {
                    $currentMatcher = $this->blueprint->current();
                    list($currentMatcherFn, $currentMatcherFlag, $currentMatcherArgs) = $currentMatcher;

                    $currentMatcherArgs = $currentMatcherArgs ?: [];

                    $currentValue = $againstIterator->current();


                    switch($currentMatcherFlag) {
                        case static::STRICT:
                            if($currentMatcherFn($currentValue)) {
                                $eval = 1;

                                $againstPointer = $againstIterator->key();

                                $matches[$this->blueprint->key()] = $lastMatch = [$againstPointer, $againstPointer];

                                $this->blueprint->next();
                                $againstIterator->next();
                            }
                            else {
                                $eval = 0;
                            }
                            break;
                        case static::SPREAD:
                            $againstStartPointer = $againstIterator->key();

                            $precedenceExit = false;

                            while(($againstIterator->valid() ? $currentMatcherFn($currentValue) : false) && !$precedenceExit) {
                                $againstIterator->next();
                                $currentValue = $againstIterator->current();

                                if($this->precedence === Matcher::PRECEDENCE_LAZY)
                                    $precedenceExit = true;
                            }

                            $matches[$this->blueprint->key()] = $lastMatch = [
                                $currentMatcherFlag,
                                $againstStartPointer, $againstIterator->key()-1
                            ];
                            $this->blueprint->next();

                            break;
                        // case "times":
                        //     $currentMatcherLength   = 0;
                        //     $currentMatcherBehindBy = 0;

                        //     $currentMatcherMin = $currentMatcherArgs["min"];
                        //     $currentMatcherMax = $currentMatcherArgs["max"];

                        //     // Remember original position
                        //     $againstIterator->anchor();

                        //     while(($currentMatcherLength <= $currentMatcherMax) ? ($againstIterator->valid() ? $currentMatcherFn($currentValue) : false) : false) {
                        //         $currentMatcherLength++;
                        //         $againstIterator->next();
                        //         $currentValue = $againstIterator->current();
                        //     }

                        //     $againstEndPointer = $againstIterator->key();

                        //     $againstIterator->revert();

                        //     $againstIterator->prev();

                        //     $currentValue = $againstIterator->current();

                        //     while(($currentMatcherLength <= $currentMatcherMax) ? ($againstIterator->valid() ? $currentMatcherFn($currentValue) : false) : false) {
                        //         $currentMatcherLength++;
                        //         $currentMatcherBehindBy++;
                        //         $againstIterator->prev();
                        //         $currentValue = $againstIterator->current();
                        //     }

                        //     $againstStartPointer = $againstIterator->key();

                        //     $againstIterator->seek($againstEndPointer);
                            
                        //     if($currentMatcherLength >= $currentMatcherMin) {
                        //         $matches[\Arr::lastKey($matches)][1] -= $currentMatcherBehindBy;

                        //         $matches[$this->blueprint->key()] = $lastMatch = [$currentMatcherFlag, $againstStartPointer+1, $againstEndPointer-1];
                        //         $this->blueprint->next();
                        //     }
                        //     else {
                        //         $currentMatcherRemaining = $currentMatcherMin - $currentMatcherLength;

                        //         $lastSpreadMatch = null;

                        //         for($index = count($matches); $index >= 0; $index--) {
                        //             $match = $matches[$index];

                        //             switch($match[0]) {
                        //                 case "strict":
                        //                     $index = -1;
                        //                     break;
                        //                 case "spread":
                        //                     $lastSpreadMatch = $index;
                        //                     break;
                        //             }
                        //         }

                        //         $eval = false;
                        //     }
                        //     break;
                        default:
                            throw new \Error("Unknown matcher flag {$currentMatcherFlag}.");
                            break;
                    }

                    unset($currentValue);
                    $lastMatcher = $currentMatcher;
                }
                else {
                    $eval = 0;
                    // throw new \Error("Blueprint iterator prematurely ended.");
                }
            }

            return [(bool)$eval, $matches];
        }
    }
}

?>