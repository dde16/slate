<?php declare(strict_types = 1);

namespace Slate\Foundation\Console {

    use OutOfBoundsException;
    use RuntimeException;
    use Slate\Data\Iterator\ArrayExtendedIterator;

    class ArgumentParser {
        public const ERROR_UNKNOWN_ARGUMENT = (1<<0);
        public const ERROR_INSUFFICIENT_ARGUMENT_VALUES = (1<<1);

        protected array $arguments;
        protected array $map;

        public function __construct() {
            $this->arguments = [];
            $this->map = [];
        }

        /**
         * Get the arguments set on this parser.
         *
         * @return Argument[]
         */
        public function getArguments(): array {
            return $this->arguments;
        }

        public function addArgument(
            string $param,
            string $type = "string",
            array|string|null $aliases = null,
            ?int $nargs = null,
            ?string $help = null
        ) {
            $argument = new Argument($param, $type, $aliases, $nargs, $help);

            if(\Arr::hasKey($this->arguments, $argument->getName()))
                throw new RuntimeException("Duplicate argument name $param.");

            $this->arguments[$param] = $argument;

            foreach($argument->getAliases() as $alias) {
                if(\Arr::hasKey($this->map, $alias)) {
                    throw new RuntimeException("Duplicate alias '$alias' on argument $param.");
                }

                $this->map[$alias] = &$this->arguments[$param];
            }
        }

        public static function fromArray(array $arguments): static {
            $parser = new static;

            foreach($arguments as $name => $options) {
                $parser->addArgument(...array_merge_recursive(["param" => $name], $options));
            }

            return $parser;
        }

        public function parse(array $argv, bool $assert = true): array {
            $nargs     = -1;
            $remaining = 0;
            $arguments = [];
            $iter = new ArrayExtendedIterator($argv);

            if (!\Arr::isEmpty($argv)) {
                $argument  = null;

                while ($iter->valid()) {
                    $arg = $iter->current();

                    if (($_argument = $this->map[$arg]) !== null) {
                        /** If the last argument has unfinished parsing */
                        if ($nargs !== -1 && $remaining > 0)
                            break;

                        $argument = $_argument;
                        $remaining = $nargs = $argument->getNargs();

                        /** If the nargs is zero then the argument is a flag. */
                        if ($nargs === 0) {
                            $arguments[$argument->getName()] = true;
                            $argument = null;
                        }
                    }
                    else if ($argument !== null) {
                        /** If the nargs are -1 and not 1, then it requires more than one argument (as an array) */
                         if ($nargs === -1 || $nargs !== 1) {
                            $arguments[$argument->getName()][] = $arg;

                            /** When we are finished, start parsing a new argument */
                            if ($remaining-- === 0)
                                $argument = null;
                        }
                        /** If the nargs are 1, then it is a simple value */
                        else if ($nargs === 1 && $remaining === 1) {
                            $arguments[$argument->getName()] = $argument->parse($arg);
                            $remaining--;
                            $argument = null;
                        }
                        /** Else then reparse the current as a new argument */
                        else {
                            $argument = null;
                            $iter->prev();
                        }
                    }
                    /** If an argument wasnt found */
                    else {
                        if($assert)
                            throw new RuntimeException(
                                "Unknown argument '$arg'.",
                                static::ERROR_UNKNOWN_ARGUMENT
                            );

                        try {
                            $iter->prev();
                        }
                        catch(OutOfBoundsException) {}
                        break;
                    }

                    $iter->next();
                }

                /** If the last argument has unfinished parsing */
                if ($argument ? $remaining > 0 : false)
                    throw new RuntimeException(\Str::format(
                        "Argument {} requires {} argument{}, {} passed.",
                        \Arr::join($argument->getAliases(), "/"),
                        $nargs,
                        $nargs > 1 ? 's' : '',
                        $nargs - $remaining
                    ), static::ERROR_INSUFFICIENT_ARGUMENT_VALUES);
            }

            return [$arguments, $iter->rest()];
        }
    }
}

?>