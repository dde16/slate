<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter {

    use Closure;
    use Slate\Lang\Interpreter\Tokeniser\TokenMatch;
    use Slate\Lang\Interpreter\Attribute\TokenAttribute;
    use Slate\Data\Iterator\BufferedIterator;
    use Slate\Data\Iterator\StringIterator;
    use Slate\IO\File;

    use Generator;
    use Slate\Lang\Interpreter\Attribute\Token;

    trait TParser {
        use TTokeniser;

        public abstract function parse(): Generator;

        protected function expectWrapped(Closure $between, string|int $wrapperKey, bool $raise = false, bool $raiseEof = false) {
            if($leftToken = $this->matchToken($wrapperKey)) {
                $betweenToken = $between();

                if($rightToken = $this->matchToken($wrapperKey, $raise, $raiseEof)) {
                    return $betweenToken;
                }
                else if($raise) {
                    throw new \Error(\Str::format("Unclosed group near {}.", $leftToken->counters["pointer"]));
                }

                $this->pointer--;
            }
        }

        protected function expectTokenAfter(Generator $tokens, Closure|int $afterToken, bool $afterReturn = false): Generator {
            foreach($tokens as $token)
                yield $token;

            if(is_int($afterToken))
                $afterToken = fn(): ?TokenMatch => $this->matchToken($afterToken, raise: true, raiseEof: true) ? null : null;

            $afterResult = $afterToken();

            if($afterReturn)
                yield $afterResult;
        }

        protected function expectList(Closure $between, int|Closure $delimiter, bool $raise = false, bool $nonEmpty = false): Generator {
            if(is_int($delimiter))
                $delimiter = fn(): ?TokenMatch => $this->matchToken($delimiter, raise: $raise);

            if(($betweenInitialToken = $between()) !== null) {
                yield $betweenInitialToken;

                while($delimiter() !== null) {
                    if(($betweenIntermediateToken = $between()) !== null) {
                        yield $betweenIntermediateToken;
                    }
                    else {
                        throw new \Error("Expecting item after the delimiter.");
                    }
                }
            }
            else if($nonEmpty) {
                throw new \Error("List must have an initial item.");
            }
        }

        public function expectZeroPlus(int $key): Generator {
            if(($initialToken = $this->matchToken($key)) !== null) {
                yield $initialToken;

                while(($nextToken = $this->matchToken($key)) !== null) {
                    yield $nextToken;
                }
            }
        }

        public function expectOnePlus(int $key): Generator {
            if(($initialToken = $this->matchToken($key, raise: true)) !== null) {
                yield $initialToken;

                while(($nextToken = $this->matchToken($key)) !== null) {
                    yield $nextToken;
                }
            }
        }

        public function expectAny(bool $raiseEof = false): ?TokenMatch {
            if(($token = $this->tokenMatches->current()) !== null) {
                $this->tokenMatches->next();

                return $token;
            }
            else if($raiseEof) {
                throw new \Error("Unexpected EOF");
            }

            return null;
        }
        
        public function expectOneOfTokens(array $keys, bool $raise = false, bool $raiseEof = false): ?TokenMatch {
            foreach($keys as $index => $key) {
                if(($tokenMatch = $this->matchToken(
                    $key,
                    raise: ($index === (count($keys) - 1)) ? $raise : false,
                    raiseEof: $raiseEof
                )) !== null) {
                    return $tokenMatch;
                }
            }
            
            return null;
        }

        public function expectBetween(int|Closure $token, int|Closure $left, int|Closure $right, bool $raise = false): Generator {
            $token = $this->tokenClosureOf($token);
            $left = $this->tokenClosureOf($left);
            $right = $this->tokenClosureOf($right);

            if(!$left($raise, $raise))
                return null;

            $tokens = $token($raise, $raise);

            if($tokens instanceof Generator) {
                foreach($tokens as $subtoken) {
                    yield $subtoken;
                }
            }
            else {
                yield $tokens;
            }

            $right(true, true);
        }

        public function expectNoneOf(array|int $keys, bool $raise = true,  bool $raiseEof = false) {
            $keys = \Arr::always($keys);

            $this->code->anchor();

            foreach($keys as $key) {
                if($this->matchToken($key, false, $raiseEof)) {
                    if($raise)
                        $this->raiseNonMatch();

                    $this->code->revert();
                    return false;
                }
            }

            return true;
        }

        public function tokenClosureOf(int|Closure $token): Closure {
            if(is_int($token))
                $token = fn(bool $raise = false, bool $raiseEof = false): ?TokenMatch => $this->matchToken($token, $raise, $raiseEof);

            return $token;
        }

        // public function expectToken(int $key, bool $raise = false, bool $raiseEof = false): ?TokenMatch {
        //     if(($token = $this->tokenMatches->current()) !== null) {
        //         if($token->id === $key) {
        //             $this->tokenMatches->next();
        //             return $token;
        //         }
        //         else if($raise) {
        //             throw new \Error(\Str::format(
        //                 "Unexpected token '{}' (type {}) at position {}, expecting {}",
        //                 \Str::controls($token->getValue($this->code), ["\n" => "\\n", "\r" => "\\r"]),
        //                 \Str::title(\Str::lower($token->name)),
        //                 $token->counters["pointer"],
        //                 \Str::title(\Str::lower(static::design()->tokens[$key]->parent->getName())),
        //             ));
        //         }
        //     }
        //     else if($raiseEof) {
        //         throw new \Error("Unexpected EOF");
        //     }

        //     return null;
        // }
    }
}

?>