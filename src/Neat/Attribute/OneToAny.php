<?php

namespace Slate\Neat\Attribute {

    use ReflectionProperty;
    use Slate\Metalang\MetalangAttribute;
    use Slate\Metalang\MetalangDesign;
    use Slate\Neat\Entity;
    use Slate\Neat\EntityDesign;

    class OneToAny extends MetalangAttribute {
        public ?string $foreignImmediateClass = null;
        public ?string $foreignImmediateProperty = null;
    
        public string $localProperty;
    
        public function __construct(string $localProperty, array $foreignRelalationship) {
            $this->localProperty = $localProperty;
            $this->setForeignRelationship($foreignRelalationship);
        }

        public function getLocalProperty(): string {
            return $this->localProperty;
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

        public function getForeignDesign(): EntityDesign {
            return $this->getForeignClass()::design();
        }
    
        public function setForeignRelationship(array $foreignRelationship): void {
            list($foreignClass, $foreignProperty) = $foreignRelationship;
    
            if(!is_string($foreignClass) || !is_string($foreignProperty))
                throw new \Error("Invalid " . static::class . " foreign relationship as it must be an array in the form [class:string, property:string].");

            if(!class_exists($foreignClass))
                throw new \Error(\Str::format(
                    "Error when defining {} attribute as the foreign class {} doesnt exist.",
                    \Str::afterLast(static::class, "\\"),
                    $foreignClass
                ));

            if(!\Cls::isSubclassInstanceOf($foreignClass, Entity::class))
                throw new \Error(\Str::format(
                    "Error when defining {} attribute as the foreign class '{}' isn't an entity.",
                    \Str::afterLast(static::class, "\\"),
                    $foreignClass
                ));

            $this->foreignImmediateClass = $foreignClass;
            $this->foreignImmediateProperty = $foreignProperty;
        }
    }
}

?>