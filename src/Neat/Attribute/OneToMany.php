<?php

namespace Slate\Neat\Attribute {
    use Attribute;
    use ReflectionProperty;
    use Slate\Metalang\MetalangDesign;

#[Attribute(Attribute::TARGET_PROPERTY)]
    class OneToMany extends OneToAny {        
        public function __construct(
            string $localProperty,
            array $foreignRelalationship
        ) {
            parent::__construct($localProperty, $foreignRelalationship);

            $parent = $this->parent;

            if($parent->hasType()) {
                $parentType = $parent->getType();
                $parentTypeName = $parentType->getName();
                
                if($parentTypeName !== "array")
                    throw new \Error(\Str::format(
                        "Property {}::\${} with a OneToMany defined must be of type 'array'.",
                        $parent->getDeclaringClass()->getName(),
                        $parent->getName()
                    ));
            }
        }
    }
}

?>