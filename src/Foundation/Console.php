<?php

namespace Slate\Foundation {
    use Slate\Foundation\Console\Command;
    use Slate\Foundation\Console\CommandOption;

    class Console extends App {
        public array $argv;

        public const PROVIDERS = [
            ...App::PROVIDERS,
            \Slate\Foundation\Provider\ConsoleProvider::class
        ];

        public function __construct(string $root, array $argv) {
            $this->argv = $argv;

            parent::__construct($root);
        }

        public static function coloured(string $text, int $colour): string {
            return "\033[{$colour}m{$text}\033[0m";
        }

        public function getHelp(string $path): string {
            $help = <<<STR
Usage: {} [OPTIONS] COMMAND [ARGS]...

Options:
{}

Commands:
{}


STR;
    
            return \Str::format(
                $help,
                basename($path),
                \Arr::join(
                    \Arr::map(
                        static::design()->getAttrInstances(CommandOption::class),
                        fn(CommandOption $option): string => \Arr::join($option->getNames(), "/")
                    ),
                    "\n"
                ),
                \Arr::join(
                    \Arr::map(
                        static::design()->getAttrInstances(Command::class),
                        fn(Command $command): string => $command->getName()
                    ),
                    "\n"
                )
            );
        }

        public function getCommand(string $name): Command|null {
            debug(static::design()->getAttrInstances(Command::class));
            return \Arr::first(
                static::design()->getAttrInstances(Command::class),
                function($command) use($name) {
                    return $command->getName() === $name;
                }
            );
        }
    
        public function getCommandOption(string $name): CommandOption|null {
            return \Arr::first(
                static::design()->getAttrInstances(CommandOption::class),
                function($option) use($name) {
                    return \Arr::contains(
                        $option->getNames(),
                        $name
                    );
                }
            );
        }
    }
}

?>