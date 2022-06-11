<?php declare(strict_types = 1);

namespace Slate\Metalang {
    use ReflectionAttribute;
    use ReflectionClass;
    use ReflectionClassConstant;
    use ReflectionMethod;
    use ReflectionParameter;
    use ReflectionProperty;
    use Slate\Exception\SlateException;
    use Throwable;

class MetalangClassConstructAttribute extends MetalangClassConstruct {
        public function __construct(
            MetalangClassConstruct $parent,
            ReflectionAttribute $construct
        ) { 
            $this->parent = $parent;
            $this->construct = $construct;
        }

        public function newInstance(): array {
            $attribute = $this->getName();

            $class = $this->construct->getName();
            $reflection = new ReflectionClass($class);
            
            // try {
                /**
                 * While I acknowledge this is an anti-pattern, this is to ensure I dont pass the parent
                 * at the end of the constructor - which then disallows the use of spread parameters.
                 * The object will only be uninitialised for a very small amount of time.
                 */
                $instance = $reflection->newInstanceWithoutConstructor();
                $instance->setParent($this->parent->construct);
                $instance->__construct(...$this->construct->getArguments());
            // }
            // catch(\Error $throwable){ 
            //     $parent = $this->parent->construct;

            //     if(\Cls::isSubclassInstanceOf($parent, ReflectionParameter::class)) {
            //         //TODO
            //     }
            //     else {
            //         $parentRef = $parent->getDeclaringClass()->getName();

            //         if(\Cls::isSubclassInstanceOf($parent, [ReflectionMethod::class, ReflectionClassConstant::class])) {
            //             $parentRef .= "::" . $parent->getName();
            //         }
            //         else if(\Cls::isSubclassInstanceOf($parent, ReflectionProperty::class)) {
            //             $parentRef .= "->\$" . $parent->getName();
            //         }
            //     }

            //     throw new \Error("Error while instantiating Attribute {$class} for {$parentRef}: " . $throwable->getMessage());
            // }
    
            if(\Cls::isSubclassInstanceOf($attribute, MetalangAttribute::class))
                $instance->consume($this->parent->construct);
    
            return [$attribute, $instance];
        }
    }
}

?>