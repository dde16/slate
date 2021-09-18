<?php

namespace Slate\Metalang\Attribute {
    use Slate\Metalang\MetalangAttribute;
    
    abstract class AttributeImplementor extends MetalangAttribute {
        protected string $targetAttribute;
        protected array  $trailingAttributes;
    
        public function __construct(string $target, array $trailing = []) {
            $this->targetAttribute = $target;
            $this->trailingAttributes = $trailing;
        }
            
        public function getKeys(): string|array {
            return $this->getTargetAttribute();
        }
    
        public function getTargetAttribute(): string {
            return $this->targetAttribute;
        }

        public function getTrailingAttributes(): array {
            return $this->trailingAttributes;
        }
    }
}

?>