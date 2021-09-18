<?php

namespace Slate\Lang\Interpreter {

    use Slate\Data\Iterator\BufferedIterator;

    use Generator;
    use Slate\Lang\Interpreter\Attribute\Evaluator;

trait TEvaluator {
        protected array $counters   = [];
        
        protected function evaluateElement(array $construct, array &$arguments ) {
            $this->counters["depth"][$construct[0]] += 1;

            if($construct[1] !== null) {
                if(is_object($construct[1]) ? \Cls::isSubclassInstanceOf($construct[1], [Generator::class]) : false) {
                    $construct[1] = new BufferedIterator($construct[1]);
                    
                    while($construct[1]->valid()) {
                        $child = &$construct[1]->current();

                        $construct[1]->intermediate[$construct[1]->key()] = is_array($child) ? $this->evaluateElement($child, $arguments) : $child;

                        $construct[1]->next();
                    }
    
                    $construct[1] = $construct[1]->intermediate;
                }
            }

            if(($evaluator = static::design()->getAttrInstance(Evaluator::class, $construct[0]))) {
                $this->counters["depth"][$construct[0]] -= 1;


                $this->{$evaluator->parent->getName()}($construct, $arguments);
            }

            return $construct;
        }
    
        protected function evaluate(Generator|array $parseTree, array &$arguments): Generator {
            $this->counters["depth"] = [];
            $this->counters["count"] = [];

            foreach($parseTree as $construct) {
                yield $this->evaluateElement($construct, $arguments);
                // debug(count($this->tokenMatches));
                $this->tokenMatches->clear();
                // debug(count($this->tokenMatches));
            }
        }
    }
}

?>