<?php

namespace Slate\Foundation\Provider {

    use Slate\Facade\App;
    use Slate\Foundation\Provider;
    use Slate\IO\SysvResource;
    use Slate\IO\SysvSharedMemoryFactory;
    use Slate\Mvc\Env;

    class ShmProvider extends Provider {
        public function register(): void {
            $this->app->shm = [];
            
            if(Env::has("shm")) {
                $shms = Env::array("shm", assert: "Configuration variable 'shm' must be of type array.");

                foreach($shms as $name => $shm) {
                    $type = $shm["type"];

                    if(!is_string($type))
                        throw new \Error("Shm '{}' must have a type.");

                    $this->shm[$name] = SysvSharedMemoryFactory::create($type, \Arr::except($shm, ["type"]));
                }
            }

            App::contingentMacro("shm", function(string $name): SysvResource|null {
                return @$this->shm[$name];
            });
        }
    }
}

?>