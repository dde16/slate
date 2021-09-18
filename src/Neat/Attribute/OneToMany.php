<?php

namespace Slate\Neat\Attribute {
    use Attribute;

    #[Attribute(Attribute::TARGET_PROPERTY)]
    class OneToMany extends OneToAny {
        public const NAME = "OneToMany";
    
        public function consume($property): void {
            parent::consume($property);

            if($property->hasType()) {
                $propertyType = $property->getType();
                $propertyTypeName = $propertyType->getName();
                
                if($propertyTypeName !== "array")
                    throw new \Error(\Str::format(
                        "Property {}::\${} with a OneToMany defined must be of type 'array'.",
                        $property->getDeclaringClass()->getName(),
                        $property->getName()
                    ));
            }
        }
    }
}

?>