<?php

namespace Slate\Neat\Attribute {
    use Attribute;
    use Slate\Metalang\MetalangAttribute;
    
    #[Attribute(Attribute::TARGET_METHOD)]
    class Alias extends MetalangAttribute {
        public const NAME = "Alias";
    
        protected string $alias;
    
        public function __construct(string $alias) {
            $this->alias = $alias;
        }
    
        public function getKeys(): string|array {
            return($this->parent->isStatic() ? "static." : "") . $this->alias;
        }
    }
}

?>