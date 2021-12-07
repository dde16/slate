<?php

namespace Slate\Foundation\Console {

    use Attribute;
    use ReflectionType;
    use Slate\Metalang\MetalangAttribute;
    use Slate\Metalang\MetalangDesign;

    abstract class CommandExtra extends MetalangAttribute {
        protected ?array  $names;
        protected ?string $help;
    
        /**
         * nargs = -1 : List of arguments
         * nargs = 0  : Zero arguments (a flag)
         * nargs = 1  : One argument
         */
        protected ?int    $nargs;
    
        public function __construct(string|array $name = null, string $help = null, int $nargs = null, mixed $design = null) {
            $this->names    = $name !== null ? (is_string($name) ? [$name] : $name) : null;
            $this->help     = $help;
            $this->nargs    = $nargs;
        }

    
        public function getNames(): array {
            return $this->names ?? [$this->parent->getName()];
        }
    
        public function getHelp(): ?string {
            return $this->help;
        }
    
        public function getNargs(): int {
            return $this->nargs ?? 1;
        }
    
        public abstract function isRequired(): bool;
    
        public function consume($construct): void {
            parent::consume($construct);
    
            if($this->nargs === null) {
                if($type = $this->parent->getType()) {
                    if($type instanceof ReflectionType) {
                        switch($type->getName()) {
                            case "array":
                                $this->nargs = -1;
                                break;
                            case "bool":
                                $this->nargs = 0;
                                break;
                        }
                    }
                }
            }
        }
    }
}

?>