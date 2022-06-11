<?php declare(strict_types = 1);

namespace Slate\Foundation\Console\Command {

    use RuntimeException;
    use Slate\Data\Table;
    use Slate\Foundation\Console\Command;

    class HelpCommand extends Command {
        public const NAME = "help";
        public const ARGUMENTS = [
            "commandName" => [
                "aliases" => [
                    "-c", "--cmd", "--command"
                ]
            ]
        ];

        public function getApplicationHelp(): string {
            $help = <<<STR
Usage: {} [OPTIONS] COMMAND [ARGS]...

Options:
{}

Commands:
{}


STR;

            return \Str::format(
                $help,
                basename($this->app->path),
                "None",
                \Arr::join(
                    \Arr::map(
                        $this->app->getCommands(),
                        fn (Command $command): string => $command::NAME ?? throw new RuntimeException()
                    ),
                    "\n"
                )
            );
        }

        public function getCommandHelp(Command $command): string {
            $parser = $command->getParser();
            $help = <<<STR
Usage: {} [OPTIONS] {} [ARGS]...

Arguments:
{}
STR;
            $argumentsTable = new Table(["name", "type", "length", "description"]);

            foreach ($parser->getArguments() as $argument) {
                $argumentsTable->rows[] = [
                    \Arr::join($argument->getAliases(), "/"),
                    $argument->getType()::NAMES[0],
                    $argument->getNargs(),
                    $argument->getHelp() ?? ""
                ];
            }

            return \Str::format(
                $help,
                basename($this->app->path),
                $command::NAME,
                $argumentsTable->toTableString(delimitRows: false, delimitColumns: false, padsize: ["left" => 0, "right" => 2])
            ) . "\n";
        }

        public function handle(string $commandName = null): void {
            $commands = $this->app->getCommands();

            if($commandName !== null) {
                if(!\Arr::hasKey($commands, $commandName))
                    throw new RuntimeException("Unknown command '$commandName'.");

                echo $this->getCommandHelp($commands[$commandName]);
            }
            else {
                echo $this->getApplicationHelp();
            }
        }
    }
}

?>