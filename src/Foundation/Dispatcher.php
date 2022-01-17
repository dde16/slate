<?php

namespace Slate\Foundation {

    use Closure;
    use RuntimeException;
    use Slate\Data\Structure\IQueue;
    use Slate\Facade\App;
    use Slate\IO\SysvSharedMemoryQueue;
    use Slate\IO\SysvSharedMemoryTableQueue;

    class Dispatcher {
        /**
         * Stores the name of the queue to which events are emitted.
         * 
         * @var string
         */
        protected ?string $queue;

        /**
         * Stores the listeners for each event.
         *
         * @var array
         */
        protected array $listeners;

        /**
         * Stores the allowed events.
         *
         * @var array
         */
        protected array $events;

        public function __construct(?string $queue = null) {
            $this->listeners = [];
            $this->events = [];
            $this->queue = $queue;
        }

        public function on(string $event, Closure ...$closures): static {
            if(!\Arr::hasKey($this->listeners, $event))
                $this->listeners[$event] = [];

            foreach($closures as $closure)
                $this->listeners[$event][] = $closure;

            return $this;
        }

        public function define(string $event, Closure ...$closures): static {
            return $this->on($event, ...$closures);
        }

        /**
         * Emits an event to the current dispatcher.
         *
         * @return void
         */
        public function emit(string $event, array $arguments = []): void{
            \Fnc::chain(@$this->listeners[$event] ?? [], $arguments);
        }

        /**
         * Emits an event to the queue.
         *
         * @param string $event
         * @param array $arguments
         *
         * @return void
         */
        public function dispatch(string $event, array $arguments = []): void {
            if($this->queue === null)
                throw new RuntimeException("Unable to dispatch events to an undefined queue.");

            /** @var IQueue $queue  */
            $queue = App::queue($this->queue);
            
            $queue->enqueue([
                "name"      => $event,
                "arguments" => $arguments
            ]);

            $this->emit($event, $arguments);
        }

        protected function listener(IQueue $queue): void {
            if(!$queue->isEmpty()) {
                $event = $queue->dequeue();

                $this->emit($event["name"], $event["arguments"]);
            }
        }

        public function listen(int|float $timeout = 0, bool $reactphp = false): void {
            /** @var IQueue $queue */
            $queue = App::queue($this->queue);

            if(!$reactphp) {
                while(true) {
                    $this->listener($queue);

                    time_sleep_until(microtime(true) + $timeout);
                }
            }
            else if(class_exists(\React\EventLoop\Loop::class)) {
                \React\EventLoop\Loop::addPeriodicTimer($timeout, function() use($queue) {
                    $this->listener($queue);
                });
            }
            else {
                throw new RuntimeException("ReactPHP is specified as the queue event loop where it is not installed.");
            }
        }
    }
}

?>