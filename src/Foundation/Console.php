<?php declare(strict_types = 1);

namespace Slate\Foundation {
    use Slate\Foundation\Console\Command;
    use Slate\Foundation\Console\CommandOption;

    use Error;
    use ReflectionUnionType;
    use RuntimeException;
    use Slate\Data\Iterator\ArrayExtendedIterator;
    use Slate\Foundation\Console\ArgumentParser;

    //TODO: add type hinting from handle functions
    //TODO: add better message for missing (required) arguments
    class Console extends App {
        public string $path;
        public array $argv;
        public static int   $indent = 0;

        public const PROVIDERS = [
            ...App::PROVIDERS,
            \Slate\Foundation\Provider\ConsoleProvider::class
        ];

        public const COMMANDS = [
            \Slate\Foundation\Console\Command\HelpCommand::class,
            \Slate\Foundation\Console\Command\MakeMigrationCommand::class,
            \Slate\Foundation\Console\Command\MigrateCommand::class,
            \Slate\Foundation\Console\Command\ClassDocsCommand::class,
            \Slate\Foundation\Console\Command\ClassMethodsCommand::class,
            \Slate\Foundation\Console\Command\Sysv\SysvDestroyCommand::class,
            \Slate\Foundation\Console\Command\Sysv\SysvMessageQueueSendCommand::class,
            \Slate\Foundation\Console\Command\Sysv\SysvMessageQueueWatchCommand::class,
            \Slate\Foundation\Console\Command\Sysv\SysvProbeCommand::class,
            \Slate\Foundation\Console\Command\FileSystem\LinkCommand::class,
            \Slate\Foundation\Console\Command\FileSystem\UnlinkCommand::class,
        ];

        public const COMMAND_FALLBACK = "help";

        public const OPTIONS = [];

        /**
         * Collection of instantiated commands.
         *
         * @var Command[]
         */
        protected array $commands;

        public function __construct(string $root, array $argv) {
            $this->path = $argv[0];
            $this->argv = array_slice($argv, 1);
            $this->commands = \Arr::mapAssoc(
                static::COMMANDS,
                fn(mixed $index, string $command): array => [
                    $command::NAME,
                    new $command($this)
                ]
            );

            parent::__construct($root);
        }

        public static function getIndentation(): int {
            return static::$indent;
        }

        public static function indent(): void {
            static::$indent++;
        }

        public static function outdent(): void {
            static::$indent--;
        }

        /**
         * Get the commands defined for this application.
         *
         * @return Command[]
         */
        public function getCommands(): array {
            return $this->commands;
        }

        public static function coloured(string $text, int $colour): string {
            return "\033[{$colour}m{$text}\033[0m";
        }

        public static function clear(): void {
            echo chr(27).chr(91).'H'.chr(27).chr(91).'J';
        }

        public function go(): void {
            $argv = $this->argv;
            $optionsParser = new ArgumentParser;

            foreach (\Arr::associate(static::OPTIONS, null) as $optionName => $optionAliases) {
                $optionAliases ??= "--".\Str::kebab($optionName);
                $optionsParser->addArgument(param: $optionName, type: "bool", aliases: $optionAliases);
            }

            [$options, $argv] = $optionsParser->parse($argv, false);

            $commandName = array_shift($argv) ?? static::COMMAND_FALLBACK;

            if (!\Arr::hasKey($this->commands, $commandName)) {
                debug("Unknown command '$commandName'.");

                $commandSimilarNames = \Arr::filter(\Arr::keys($this->commands), fn(string $key): bool => \fnmatch("*{$commandName}*", $key));

                if(count($commandSimilarNames) > 0) {
                    debug();
                    debug("Did you mean:");
                    debug(\Arr::join($commandSimilarNames, "\n"));
                }
                return;
            }

            $command = $this->commands[$commandName];

            $arguments = [];

            if ($argv !== null) {
                $argumentsParser = $command->getParser();
                [$arguments] = $argumentsParser->parse($argv);
            }

            if (\Obj::hasPublicMethod($command, "handle")) {
                $command->options = $options;
                $command->handle(...$arguments);
            }
        }
    }
}

?>