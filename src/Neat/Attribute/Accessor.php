<?php

namespace Slate\Neat\Attribute {
    use Attribute;
    use ReflectionMethod;
    use Slate\Metalang\Prefab\MetalangNamedAttribute;
    use Slate\Metalang\MetalangDesign;

    #[Attribute(Attribute::TARGET_METHOD)]
    abstract class Accessor extends MetalangNamedAttribute {
        protected ?string $for;

        public function __construct(string $property, ?string $for = null) {
            parent::__construct($property);

            $this->for     = $for;
        }
    
        public function getProperty(): string {
            return $this->name;
        }

        public function getFor(): string {
            return $this->for ?: $this->name;
        }
    }
}

?>