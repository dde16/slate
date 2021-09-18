<?php

namespace Slate\Neat\Attribute {
    use Attribute;
    use Slate\Metalang\Prefab\MetalangNamedAttribute;

    #[Attribute(Attribute::TARGET_METHOD)]
    abstract class Accessor extends MetalangNamedAttribute {    
        public function __construct(string $property) {
            $this->name = $property;
        }
    
        public function getProperty(): string {
            return $this->name;
        }
    }
}

?>