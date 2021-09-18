<?php

namespace Slate\Neat\Attribute {

    use Attribute;
    use Slate\Metalang\MetalangAttribute;

    #[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
    class Initialiser extends MetalangAttribute {
        protected string $propertyName;

        public function __construct(string $property) {
            $this->propertyName = $property;
        }

        public function getKeys(): array|string {
            return $this->propertyName;
        }

        public function getProperty(): string {
            return $this->propertyName;
        }
    }
}

?>