<?php declare(strict_types = 1);

namespace Slate\Foundation\Provider {

    use Closure;
    use Slate\Facade\App;
    use Slate\Foundation\Provider;
    use Slate\Sysv\SysvResource;
    use Slate\Sysv\SysvSharedMemoryFactory;
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

                    $this->shm[$name] = $shm;
                }
            }

            App::contingentMacro("shms", function(?Closure $filter = null): array {
                $shms = [];

                if($filter) {
                    $shms = \Arr::keys(\Arr::filter($this->shm, $filter));
                }
                else {
                    $shms = \Arr::keys($this->shm);
                }

                return $shms;
            });

            App::contingentMacro("shm", function(string $name): SysvResource|null {
                $shm = &$this->shm[$name];

                if(is_array($shm))
                    $shm = SysvSharedMemoryFactory::create($shm["type"], \Arr::except($shm, ["type"]));

                return $shm;
            });
        }
    }
}

?>