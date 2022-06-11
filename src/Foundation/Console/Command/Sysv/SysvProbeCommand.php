<?php declare(strict_types = 1);

namespace Slate\Foundation\Console\Command\Sysv {
    use RuntimeException;
    use Slate\Facade\App;
    use Slate\Foundation\Console\Command;
    use Slate\IO\Stream;
    use Slate\Sysv\SysvMessageQueue;

    class SysvProbeCommand extends Command {
        public const NAME = "sysv:probe";

        // public const ARGUMENTS = [
        //     "name" => [
        //         "aliases" => ["-n", "--name"],
        //         "nargs" => 1
        //     ]
        // ];

        public function handle() {
            App::repo("shm:persist")->put("last_file", "8.txt");
        }
    }
}