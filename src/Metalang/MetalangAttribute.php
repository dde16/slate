<?php declare(strict_types = 1);

namespace Slate\Metalang {
    use Reflector;
    use ReflectionClass;
    use ReflectionMethod;
    use ReflectionProperty;
    use ReflectionClassConstant;
    use Attribute;
    use ReflectionParameter;

    abstract class MetalangAttribute {
        private bool $bootstrapped = false;

        public null|ReflectionClass|ReflectionMethod|ReflectionProperty|ReflectionClassConstant|ReflectionParameter $parent = null;

        /**
         * Ensures all attributes have a constructor dependency injection.
         */
        public function __construct() { }

        /**
         * This is a one-time dependency injection function for the class construct the attribute belongs to.
         *
         * @param ReflectionClass|ReflectionMethod|ReflectionProperty|ReflectionClassConstant|ReflectionParameter $parent
         *
         * @return void
         */
        public function setParent(ReflectionClass|ReflectionMethod|ReflectionProperty|ReflectionClassConstant|ReflectionParameter $parent): void {
            if($this->parent !== null)
                throw new \Error("A parent is already defined for this Attribute.");

            $this->parent = $parent;
        }
        
        /**
         * This function is ran once the parent design is finished instantiating attributes.
         *
         * @param MetalangDesign $design
         *
         * @return void
         */
        public function bootstrap(MetalangDesign $design): void {
            $this->bootstrapped = true;
        }

        /**
         * Tells the design whether this instance has already been bootstrapped.
         *
         * @return boolean
         */
        public function isBootstrapped(): bool {
            return $this->bootstrapped;
        }

        /**
         * Returns the key(s) that will allow this attribute to be referenced.
         *
         * @return array|string
         */
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