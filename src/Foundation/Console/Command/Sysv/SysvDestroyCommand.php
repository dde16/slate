<?php declare(strict_types = 1);

namespace Slate\Foundation\Console\Command\Sysv {

    use Slate\Facade\App;
    use Slate\Foundation\Console\Command;
    use Slate\Sysv\SysvResource;

    class SysvDestroyCommand extends Command {
        public const NAME = "sysv:destroy";
        public const ARGUMENTS = [
            "name" => [
                "aliases" => ["-n", "--name"]
            ],
        ];

        public function handle(string $name): void {
            /** @var SysvResource */
            $resource = App::shm($name);
            $resource->acquire();
            $resource->destroy();
        }
    }
}

?>