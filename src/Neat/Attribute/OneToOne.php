<?php

namespace Slate\Neat\Attribute {
    use Attribute;
    use ReflectionUnionType;
    use Slate\Neat\EntityDesign;

#[Attribute(Attribute::TARGET_PROPERTY)]
    class OneToOne extends OneToAny {
        public const NAME = "OneToOne";

        public array|string|null $foreignRelationship = null;

        public function __construct(
            string $localProperty,
            array|string $foreignRelationship = null,
            string ...$foreignChainingProperties
        ) {
            $this->localProperty = $localProperty;
            $this->foreignRelationship = $foreignRelationship;
            $this->foreignChainingProperties = $foreignChainingProperties;
        }

        public function setForeignSurrogateRelationship(): void {
            $foreignRelationship = $this->foreignRelationship;
            $localProperty = $this->localProperty;
            $foreignChainingProperties = $this->foreignChainingProperties;

            if(!is_array($foreignRelationship)) {
                $localClass        = $this->parent->getDeclaringClass()->getName();
                $localDesign       = $localClass::design();

                if(($localColumnAttribute = $localDesign->getAttrInstance(Column::class, $localProperty)) === null)
                    throw new \Error("Unknown surrogate column {$localClass}::\${$localProperty}");

                $localSqlColumn = $localColumnAttribute->getColumn();

                if(!$localSqlColumn->isForeignKey())
                    throw new \Error("OneToOne surrogate column {$localClass}::\${$localProperty} is not a foreign key.");

                $foreignSchema = $localSqlColumn->getForeignSchema();
                $foreignTable  = $localSqlColumn->getForeignTable();
                $foreignColumn = $localSqlColumn->getForeignColumn();


                $foreignClass = $foreignRelationship ?: EntityDesign::byReference($foreignSchema, $foreignTable);

                if($foreignClass === null)
                    throw new \Error("Unable to resolve Entity(schema=`{$foreignSchema}`, table=`{$foreignTable}`).");

                $foreignDesign = $foreignClass::design();

                if(($foreignColumnAttribute = $foreignDesign->getColumn($foreignColumn)) === null)
                    throw new \Error("Undeclared column {$foreignClass}::\${$foreignColumn}.");

                $foreignRelationship = [
                    $foreignClass,
                    $foreignColumnAttribute->parent->getName()
                ];
            }

            $this->setForeignRelationship($foreignRelationship, $foreignChainingProperties);
        }

        public function getForeignClass(): string {
            if(!$this->foreignImmediateClass) {
                $this->setForeignSurrogateRelationship();
            }

            return parent::getForeignClass();
        }

        public function getForeignProperty(): string {
            if(!$this->foreignImmediateProperty) {
                $this->setForeignSurrogateRelationship();
            }

            return parent::getForeignProperty();
        }


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