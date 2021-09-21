<?php

namespace Slate\Neat\Attribute {
    use Attribute;
    use Slate\Metalang\MetalangAttribute;

    #[Attribute(Attribute::TARGET_PROPERTY)]
    class Json extends MetalangAttribute {

        public const NAME = "Json";

        protected ?string $path;
        protected ?string $instantiate;

        public function __construct(string $path = null, string $instantiate = null) {
            $this->path        = $path;
            $this->instantiate = $instantiate;
        }

        public function getPath(): string {
            return $this->path ?: $this->parent->getName();
        }

        public function getInstantiateClass(): string|null {
            return $this->instantiate;
        }

        public function consume($property): void {
            parent::consume($property);

            if($property->hasType()) {
                $propertyTypeName = $property->getType()->getName();

                if(\Cls::exists($propertyTypeName)) {
                    if($this->instantiate)
                        throw new \Error();

                    $this->instantiate = $propertyTypeName;
                }
            }

        }

    }
}

?>