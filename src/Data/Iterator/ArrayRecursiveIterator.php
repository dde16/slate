<?php declare(strict_types = 1);

namespace Slate\Data\Iterator {
    use Generator;
    use Iterator;
    use Slate\Data\Structure\Deque;
    use SplQueue;

    class ArrayRecursiveIterator implements Iterator {
        /**
         * The root node.
         *
         * @var object|array
         */
        protected array $root;

        /**
         * A generator to wrap around so we can rewind.
         * 
         * @var SplQueue
         */
        protected Generator $generator;

        public function __construct(object|array $root) {
            $this->root = $root;
        }

        public function rewind(): void {
            $this->generator = $this->generate();
        }

        public function current(): mixed {
            return $this->generator->current()[1];
        }

        public function key(): string|int {
            return implode(".", $this->generator->current()[0]);
        }

        public function next(): void {
            $this->generator->next();
        }

        public function valid(): bool {
            return $this->generator->valid();
        }

        protected function generate(): Generator {
            $queue = new Deque();
            $queue->enqueue([false, $this->root]);

            $path = new SplQueue;

            while(!$queue->isEmpty()) {
                [$currentKey, $currentValue] = $queue->dequeue();

                if($currentKey !== null) {
                    $currentPath = \Arr::filter([...iterator_to_array($path), $currentKey], fn($v) => $v != false);

                    if($currentKey !== false)
                        yield([$currentPath, $currentValue]);

                    if(is_array($currentValue) || (is_object($currentValue) ? (get_class($currentValue) === \stdClass::class) : false))  {
                        $queue->enqueue([null, null]);

                        $currentValueIterator = new ArrayAssociativeIterator($currentValue);

                        foreach($currentValueIterator->reverse() as [$subKey, $subValue])
                            $queue->enqueue([$subKey, $subValue]);
                        
                        if($currentKey !== false)
                            $path->enqueue($currentKey);
                    }
                }
                else if(!$path->isEmpty()) {
                    $path->dequeue();
                }
            }
        }
    }
}

?>