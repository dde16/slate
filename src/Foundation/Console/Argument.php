<?php declare(strict_types = 1);

namespace Slate\Foundation\Console {

    use RuntimeException;
    use SebastianBergmann\Environment\Runtime;
    use Slate\Exception\ParseException;

    //TODO: add 'options' for enum types, including enum classes in Slate version
    class Argument {
        protected string  $name;
        protected ?array  $aliases;
        protected ?string $help;

        /**
         * The type class for the argument.
         *
         * @var string|\ScalarType|\Arr
         */
        protected string  $type;

        /**
         * nargs = -1 : List of arguments
         * nargs = 0  : Zero arguments (a flag)
         * nargs = 1  : One argument
         * 
         * @var int
         */
        protected int    $nargs;

        public function __construct(
            string $param,
            string $type = "string",
            array|string|null $aliases = null,
            ?int $nargs = null,
            ?string $help = null
        ) {
            if(\Str::isEmpty($param))
                throw new RuntimeException("Argument cannot have an empty param name.");

            $typeClass = !class_exists($type) ? \Type::getByName($type) : $type;

            if($typeClass === null) {
                throw new RuntimeException("Argument $param specified invalid type '$type'.");
            }

            if (!(\Cls::isSubclassInstanceOf($typeClass, \ScalarType::class))) {
                throw new RuntimeException("Argument $param type must be a valid scalar type name or type class.");
            }

            if($typeClass === \Boolean::class) {
                if($nargs !== 0 && $nargs !== null) {
                    throw new RuntimeException("Argument $param of type bool cannot have nargs set as it is a flag.");
                }

                $nargs = 0;
            }

            if(is_null($aliases))
                $aliases = "--".\Str::kebab(\Str::trimPrefix($param, "--"));

            if(is_string($aliases))
                $aliases = [$aliases];

            $this->type = $typeClass;
            $this->nargs = $nargs ?? 1;
            $this->name = $param;
            $this->aliases = $aliases ?? [$param];
            $this->help = $help;
        }

        public function parse(string $value): mixed {
            try {
                $parsed = $this->type::tryparse($value);
            }
            catch(ParseException $exception) {
                throw new RuntimeException("Unable to parse value '$value' as {$this->type::NAMES[0]} for argument '{$this->name}'.");
            }

            return $parsed;
        }

        public function getType(): string {
            return $this->type;
        }

        public function getName(): string {
            return $this->name;
        }

        public function getAliases(): array {
            return $this->aliases;
        }

        public function getHelp(): ?string {
            return $this->help;
        }

        public function getNargs(): int {
            return $this->nargs;
        }
    }
}

?>