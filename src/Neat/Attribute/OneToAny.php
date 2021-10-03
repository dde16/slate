<?php

namespace Slate\Neat\Attribute {
    use Slate\Metalang\MetalangAttribute;
    use Slate\Neat\EntityDesign;

class OneToAny extends MetalangAttribute {
        public ?string $foreignImmediateClass = null;
        public ?string $foreignImmediateProperty = null;
        protected array $foreignChainingProperties;
    
    
        public string $localProperty;
    
        public function __construct(
            string $localProperty,
            array $foreignRelalationship,
            string ...$foreignChainingProperties
        ) {
            $this->localProperty = $localProperty;
            $this->setForeignRelationship($foreignRelalationship, $foreignChainingProperties);
        }

        public function getLocalProperty(): string {
            return $this->localProperty;
        }

        protected function setForeignChainingProperties(array $foreignChainingProperties): void {
            // if(\Arr::isEmpty($foreignChainingProperties))
            //     throw new \Error(\Str::format(
            //         "{} foreign properties for {}::\${} cannot be empty.",
            //         static::NAME,
            //         $this->parent->getDeclaringClass()->getName(),
            //         $this->parent->getName()
            //     ));
            
            $this->foreignChainingProperties = $foreignChainingProperties;
        }

        public function isForeignKey(): bool {
            return true;
        }
    
        public function getForeignClass(): string {
            return $this->foreignImmediateClass;
        }
    
        public function getForeignProperty(): string {
            return $this->foreignImmediateProperty;
        }
    
        public function getForeignProperties(): array {
            return $this->foreignChainingProperties;
        }

        public function hasForeignChainingProperties(): bool {
            return !\Arr::isEmpty($this->foreignChainingProperties);
        }
    
        public function getForeignChainingProperties(): array {
            return $this->foreignChainingProperties;
        }

        public function getForeignDesign(): EntityDesign {
            return $this->getForeignClass()::design();
        }
    
        public function setForeignRelationship(array $foreignRelationship, array $foreignChainingProperties): void {
            list($foreignClass, $foreignProperty) = $foreignRelationship;
    
            if(!is_string($foreignClass) || !is_string($foreignProperty))
                throw new \Error("Invalid " . static::class . " foreign relationship as it must be an array in the form [class:string, property:string].");
            

            if(!\Cls::exists($foreignClass))
                throw new \Error(\Str::format(
                    "Error when defining {} attribute as the foreign class {} doesnt exist.",
                    \Str::afterLast(static::class, "\\"),
                    $foreignClass
                ));

            $this->foreignImmediateClass = $foreignClass;
            $this->foreignImmediateProperty = $foreignProperty;
    
            $this->setForeignChainingProperties(
                $foreignChainingProperties
            );
        }
    
        public function getForeignRelationshipChain(): array {
            $chain        = [];

            $lastClass        = $this->parent->getDeclaringClass()->getName();
            $lastDesign       = $lastClass::design();
            $lastProperty     = $this->localProperty;
            $lastRelationship = $this;
    
            foreach($this->getForeignProperties() as $property) {
                $chain[] = [
                    $lastRelationship->parent->getDeclaringClass()->getName(),
                    $lastRelationship->parent->getName(),
                    [$lastClass, $lastProperty],
                    [$lastRelationship->getForeignClass(), $lastRelationship->getForeignProperty()]
                ];

                $nextClass    = $lastRelationship->getForeignClass();
                $nextProperty = $lastRelationship->getForeignProperty();
    
                if(!\Cls::isSubclassOf($nextClass, MetalangClass::class))
                    throw new \Error(\Str::format(
                        "{} relationship defined for {}::\${} points to non-metalang class {}",
                        static::NAME,
                        $this->parent->getDeclaringClass()->getName(),
                        $this->parent->getName(),
                        $class
                    ));
    
                $nextDesign = $nextClass::design();
                
                if(($nextRelationship = $nextDesign->getAttrInstance([ OneToManyAttribute::class, OneToOneAttribute::class ], $property)) !== null) {
                    $lastClass = $nextClass;
                    $lastProperty = $nextProperty;

                    $nextClass    = $nextRelationship->parent->getDeclaringClass()->getName();
                    $nextProperty    = $nextRelationship->localProperty;
                }
                else {
                    throw new \Error();
                }
    
                $chain[] = [
                    $nextRelationship->parent->getDeclaringClass()->getName(),
                    $nextRelationship->parent->getName(),
                    [$nextClass, $nextProperty],
                    [$nextRelationship->getForeignClass(), $nextRelationship->getForeignProperty()]
                ];
            }

            return $chain;
        }
    }
}

?>