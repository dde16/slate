<?php

namespace Slate\Foundation\Console {

    use Attribute;
    use Slate\Metalang\MetalangClassConstructAttributable;
    use Slate\Metalang\MetalangAttribute;

#[Attribute(Attribute::TARGET_METHOD)]
    class Command extends MetalangAttribute {
        protected ?string $name;
        protected array $arguments;
    
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
    
        public function getArgument(string $name): CommandArgument|null {
            return \Arr::first(
                $this->arguments,
                function($argument) use($name) {
                    return \Arr::contains($argument->getNames(), $name);
                }
            );
        }
    
        public function getArguments(): array {
            return $this->arguments;
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