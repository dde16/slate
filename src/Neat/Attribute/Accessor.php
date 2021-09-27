<?php

namespace Slate\Neat\Attribute {
    use Attribute;
    use Slate\Metalang\Prefab\MetalangNamedAttribute;

    #[Attribute(Attribute::TARGET_METHOD)]
    abstract class Accessor extends MetalangNamedAttribute {
        use TContextualisedAttribute;

        protected ?string $for;

        public function __construct(string $property, ?string $for = null, ?string $ctx = null) {
            $this->name    = $property;
            $this->for     = $for;
            $this->context = $ctx;
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