<?php declare(strict_types = 1);

namespace Slate\Neat\Attribute {
    use Attribute;
    use ReflectionMethod;
    use Slate\Metalang\Prefab\MetalangNamedAttribute;

    /**
     * Defines the Attribute for aliasing method
     * names based on context.
     */
    #[Attribute(Attribute::TARGET_METHOD)]
    class Alias extends MetalangNamedAttribute {    
        public function getKeys(): string|array {
            return($this->parent->isStatic() ? "static." : "") . $this->name;
        }
    }
}

?>