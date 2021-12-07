<?php

namespace Slate\Neat\Attribute {
    use Attribute;
    use ReflectionMethod;
    use Slate\Metalang\Prefab\MetalangNamedAttribute;

#[Attribute(Attribute::TARGET_METHOD)]
    class Alias extends MetalangNamedAttribute {    
        public function getKeys(): string|array {
            return($this->parent->isStatic() ? "static." : "") . $this->name;
        }
    }
}

?>