<?php

namespace Slate\Neat\Attribute {
    use Attribute;
    use ReflectionProperty;
    use Slate\Metalang\MetalangAttribute;
    use Slate\Metalang\MetalangDesign;

    #[Attribute(Attribute::TARGET_PROPERTY)]
    class Json extends MetalangAttribute {
        protected ?string $path;
        protected ?string $instantiate;
        protected bool $emptyAsNull;

        public function __construct(string $path = null, string $instantiate = null, bool $emptyAsNull = false) {
            $this->path        = $path;
            $this->instantiate = $instantiate;
            $parent = $this->parent;
            $this->emptyAsNull = $emptyAsNull;

            if($parent->hasType()) {
                $parentTypeName = $parent->getType()->getName();

                if(\class_exists($parentTypeName)) {
                    if($this->instantiate)
                        throw new \Error();

                    $this->instantiate = $parentTypeName;
                }
            }
        }

        public function getPath(): string {
            return $this->path ?: $this->parent->getName();
        }

        public function getInstantiateClass(): string|null {
            return $this->instantiate;
        }

        public function flagEmptyAsNull(): bool {
            return $this->emptyAsNull;
        }
    }
}

?>