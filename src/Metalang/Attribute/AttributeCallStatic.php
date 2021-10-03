<?php

namespace Slate\Metalang\Attribute {
    use Attribute;

    #[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
    class AttributeCallStatic extends AttributeImplementor {
        public const NAME = "AttributeCallStatic";
    
        public function consume($method): void {
            parent::consume($method);
    
            if(!$method->isStatic())
                throw new \Error(\Str::format(
                    "Attribute implementor method {}::{}() must be static.",
                    $this->parent->getDeclaringClass()->getName(),
                    $this->parent->getName()
                ));
        }
    }
}

?>