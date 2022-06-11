<?php declare(strict_types = 1);

namespace Slate\Foundation\Provider {

    use Slate\Facade\App;
    use Slate\Foundation\Provider;
    use Slate\Sysv\SysvSharedMemoryQueue;
    use Slate\Sysv\SysvSharedMemoryTableQueue;
    use Slate\Mvc\Env;
    use Slate\Mvc\QueueFactory;

    class QueueProvider extends Provider {
        public function register(): void {
            if(Env::has("queues")) {
                if(is_array($queues = Env::get("queues"))) {
                    foreach($queues as $name => $queue) {
                        $type = $queue["type"];

                        if(!is_string($type))
                            throw new \Error("Queue '{}' must have a type.");
                    
                        if($queue["default"]) {
                            if($this->primaryQueue !== null)
                                throw new \Error(\Str::format(
                                    "Trying to set '{}' as primary queue where '{}' is already the primary queue.",
                                    $name,
                                    $this->primaryQueue
                                ));

                            $this->primaryQueue = $name;
                        }

                        $this->queues[$name] = QueueFactory::create($type, \Arr::except($queue, ["type", "default"]));
                    }
                }
                else {
                    throw new \Error("Configuration variable 'queues' must be of type array.");
                }
            }

            App::contingentMacro("queues", function(): array {
                return \Arr::keys($this->queues);
            });

            App::contingentMacro("queue", function(string $name = null): SysvSharedMemoryTableQueue|SysvSharedMemoryQueue|null {
                return @$this->queues[$name ?: $this->primaryQueue];
            });
        }
    }
}

?>