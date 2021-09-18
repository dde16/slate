<?php

namespace Slate\Metalang {
    use ReflectionAttribute;
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
            
            $instance = $this->construct->newInstance();
    
            if(\Cls::isSubclassInstanceOf($attribute, MetalangAttribute::class))
                $instance->consume($this->parent->construct);
    
            return [$attribute, $instance];
        }
    }
}

?>