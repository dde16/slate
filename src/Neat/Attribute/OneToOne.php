<?php

namespace Slate\Neat\Attribute {
    use Attribute;
    use ReflectionUnionType;

    #[Attribute(Attribute::TARGET_PROPERTY)]
    class OneToOne extends OneToAny {
        public const NAME = "OneToOne";


        public function consume($property): void {
            parent::consume($property);

            // if($property->hasType()) {
            //     $propertyType = $property->getType();

            //     $errorMessage = \Str::format(
            //         "{}::\${} must have no type or a union type of string|{}, int|{} or int|string|{} and allow null.",
            //         $property->getDeclaringClass()->getName(),
            //         $property->getName(),
            //         ($foreignClass = $this->getForeignClass()),
            //         $foreignClass,
            //         $foreignClass
            //     );

            //     if($propertyType instanceof ReflectionUnionType) {
            //         if(!$propertyType->allowsNull())
            //             throw new \Error($errorMessage);

            //         $propertyUnionTypes = $propertyType->getTypes();

            //         $hasScalar = \Arr::any($propertyUnionTypes, function($propertyUnionType) {
            //             return \Arr::contains(["int", "string"], $propertyUnionType->getName());
            //         });
                    
            //         $hasForeignClass = \Arr::any($propertyUnionTypes, function($propertyUnionType) {
            //             return $propertyUnionType->getName() === $this->getForeignClass();
            //         });

            //         if(!($hasScalar && $hasForeignClass))
            //             throw new \Error($errorMessage);
            //     }
            //     else {
            //         throw new \Error($errorMessage);
            //     }
            // }
        }
    }
}

?>