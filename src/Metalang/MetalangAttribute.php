<?php

namespace Slate\Metalang {
    use Reflector;
    use ReflectionClass;
    use ReflectionMethod;
    use ReflectionProperty;
    use ReflectionClassConstant;
    use Attribute;
    use ReflectionParameter;

    abstract class MetalangAttribute {
        // Kept so it raises an error when accessed in case I missed any
        public ReflectionClass         $class;
        public ReflectionMethod        $method;
        public ReflectionProperty      $property;
        public ReflectionClassConstant $constant;
        public ReflectionParameter     $parameter;

        public ReflectionClass|ReflectionMethod|ReflectionProperty|ReflectionClassConstant|ReflectionParameter $parent;

        public function getKeys(): array|string {
            return $this->parent->getName();
        }

        public function consume(ReflectionClass|ReflectionMethod|ReflectionProperty|ReflectionClassConstant|ReflectionParameter $parent): void {
            $this->parent    = $parent;
        }
    }
}

?>