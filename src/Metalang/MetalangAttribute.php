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

        /**
         * This will pass in the parent context to the Attribute.
         * 
         * Do NOT call the getAttrInstance(s) methods inside of this function, 
         * since the attribute wont be registered; it will loop and crash.
         * 
         * @param ReflectionClass|ReflectionMethod|ReflectionProperty|ReflectionClassConstant|ReflectionParameter $parent
         * 
         * @return void
         */
        public function consume(ReflectionClass|ReflectionMethod|ReflectionProperty|ReflectionClassConstant|ReflectionParameter $parent): void {
            $this->parent    = $parent;
        }
    }
}

?>