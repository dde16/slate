<?php

namespace Slate\Foundation\Console\Command\Sysv {
    use Slate\Facade\App;
    use Slate\Foundation\Console\Command;
    use Slate\Sysv\SysvMessageQueue;

    class SysvMessageQueueWatchCommand extends Command {
        public const NAME      = "sysv:queue.watch";
        public const ARGUMENTS = [
            "name" => [
                "aliases" => ["-n", "--name"]
            ],
            "maxSize" => [
                "aliases" => ["-s", "--max-size"],
            ],
            "type" => [
                "aliases" => ["-t", "--type"],
            ],
            "parse" => [
                "aliases" => ["-p", "--parse"],
            ],
        ];

        public function handle(string $name, ?string $parse = null, string $maxSize = "4096", string $type = "0"): void {
            /** @var SysvMessageQueue */
            $sysv = App::shm($name);
            $sysv->acquire();

            $type = \Integer::tryparse($type);
            $maxSize = \Integer::tryparse($maxSize);

            while(true) {
                $message = $sysv->recieve($maxSize, $type, unserialise: false);

                switch($parse) {
                    case "json":
                        $message = json_decode($message);
                        break;
                }
                debug($message);
            }
        }
    }
}

?>