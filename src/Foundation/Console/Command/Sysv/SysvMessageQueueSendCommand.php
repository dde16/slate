<?php declare(strict_types = 1);

namespace Slate\Foundation\Console\Command\Sysv {

    use RuntimeException;
    use Slate\Facade\App;
    use Slate\Foundation\Console\Command;
    use Slate\IO\Stream;
    use Slate\Sysv\SysvMessageQueue;

    class SysvMessageQueueSendCommand extends Command {
        public const NAME = "sysv:message";
        public const ARGUMENTS = [
            "name" => [
                "aliases" => ["-n", "--name"]
            ],
            "message" => [
                "aliases" => ["-m", "--message"]
            ],
            "cast" => [
                "aliases" => ["-c", "--cast"]
            ],
            "serialise" => [
                "aliases" => ["-s", "--serialize", "--serialise"],
                "nargs"   => 0
            ],
            "nonBlocking" => [
                "aliases" => ["-nb", "--non-blocking"],
                "nargs"   => 0
            ],
            "receive" => [
                "aliases" => ["-r", "--receive"],
                "nargs"   => 0
            ],
            "type" => [
                "aliases" => ["-t", "--type"]
            ]
        ];

        public function handle(
            string $name,
            ?string $message = null,
            ?string $cast = null,
            bool $serialise = false,
            bool $nonBlocking = false,
            bool $receive = false,
            string $type = "0"
        ) {
            // $stdin = new Stream(STDIN);

            // if($stdin->getSize() !== 0) {
            //     $message = $stdin->readAll();
            // }
            // else if($message === null) {
            //     throw new RuntimeException("A message is needed to send to the Message Queue.");
            // }

            $type = \Integer::tryparse($type);

            if($cast !== null) {
                $typeClass = \Type::getByName($cast);

                if($typeClass === null)
                    throw new RuntimeException("Unknown cast type '$cast'.");

                $message = $typeClass::tryparse($message);
            }

            /** @var SysvMessageQueue */
            $sysv = App::shm($name);
            $sysv->acquire();
            $sysv->send($message, type: $type, serialise: $serialise,  block: !$nonBlocking);

            // if ($receive) {
            //     debug($message = $sysv->recieve(4096, unserialise: false));
            // }
        }
    }
}

?>