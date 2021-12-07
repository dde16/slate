<?php

namespace Slate\Foundation\Console {

    use Attribute;
    use ReflectionMethod;
    use Slate\Data\Table;
    use Slate\Metalang\MetalangClassConstructAttributable;
    use Slate\Metalang\MetalangAttribute;
    use Slate\Metalang\MetalangDesign;

#[Attribute(Attribute::TARGET_METHOD)]
    class Command extends MetalangAttribute {
        protected ?string $name;
        protected array $arguments;

        protected MetalangDesign $design;
    
        public function __construct(string $name = null) {
            $this->name = $name;
            $this->arguments  = [];
        }
    
        public function getKeys(): string|array {
            return $this->getName();;
        }
    
        public function getName(): string {
            return $this->name ?: $this->parent->getName();
        }
    
        public function getArgument(string|int $key): CommandArgument|null {
            return is_string($key)
                ? \Arr::first(
                    $this->arguments,
                    fn($argument) => \Arr::contains($argument->getNames(), $key)
                )
                : $this->arguments[$key];
        }
    
        public function getArguments(): array {
            return $this->arguments;
        }
    
        public function getHelp(string $basename): string {
            $help = <<<STR
Usage: {} [OPTIONS] {} [ARGS]...

Arguments:
{}
STR;
            $argumentsTable = new Table(["name", "length", "description"]);

            foreach($this->arguments as $argument) {
                $argumentsTable->rows[] = [
                    \Arr::join($argument->getNames(), "/"),
                    $argument->getNargs(),
                    $argument->getHelp()
                ];
            }

            return \Str::format(
                $help,
                $basename, $this->name,
                $argumentsTable->toTableString(delimitRows: false, delimitColumns: false, padsize: ["left" => 0, "right" => 2])
            )."\n";
        }
    
        public function consume($construct): void {
            parent::consume($construct);
    
            $this->arguments = \Arr::filter(\Arr::map(
                $this->parent->getParameters(),
                function($parameter)  {
                    $parameter = new MetalangClassConstructAttributable(
                        $this->parent,
                        $parameter
                    );
    
                    if(($argument = @$parameter->getAttributes(CommandArgument::class)[0]) !== null) {
                        
                        return $argument->newInstance()[1];
                    }
    
                    return null;
                }
            ));
        }
    }
}

?>