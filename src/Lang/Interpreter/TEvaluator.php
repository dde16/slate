<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter {

    use Slate\Data\Iterator\BufferedIterator;

    use Generator;
    use Slate\Lang\Interpreter\Attribute\Evaluator;

    trait TEvaluator {
        protected array $constructCounters   = [];

        public abstract function evaluate(array &$construct, array &$arguments): void;
        
        protected function evaluateConstruct(array $construct, array &$arguments ) {
            // $this->constructCounters["depth"][$construct[0]] += 1;

            if($construct[1] !== null) {
                if(is_object($construct[1]) ? \Cls::isSubclassInstanceOf($construct[1], [Generator::class]) : false) {
                    $construct[1] = new BufferedIterator($construct[1]);
                    
                    while($construct[1]->valid()) {
                        $child = &$construct[1]->current();

                        $construct[1]->intermediate[$construct[1]->key()] = is_array($child) ? $this->evaluateConstruct($child, $arguments) : $child;

                        $construct[1]->next();
                    }
    
                    $construct[1] = $construct[1]->intermediate;
                }
            }

            $this->evaluate($construct, $arguments);

            return $construct;
        }
    
        protected function evaluateTree(Generator|array $parseTree, array &$arguments): Generator {
            // $this->constructCounters["depth"] = [];
            // $this->constructCounters["count"] = [];

            foreach($parseTree as $construct) {
                yield $this->evaluateConstruct($construct, $arguments);
            }
        }
    }
}

?>