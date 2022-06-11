<?php declare(strict_types = 1);

namespace Slate\Neat\Attribute {
    use Attribute;
    use Slate\Metalang\Prefab\MetalangNamedAttribute;

    /**
     * Defines the base class for the Getter and Setter 
     * Attributes.
     */
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