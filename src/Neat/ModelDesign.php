<?php declare(strict_types = 1);

namespace Slate\Neat {

    use Slate\Metalang\MetalangDesign;
    use Slate\Metalang\MetalangTrackedDesign;
    use Slate\Neat\Attribute\Fillable;
    use Slate\Neat\Attribute\Getter;
    use Slate\Neat\Attribute\SetOnce;
    use Slate\Neat\Attribute\Setter;

    class ModelDesign extends MetalangTrackedDesign {
        public function isPropertySettable(string $propertyName): bool {
            $property = $this->getProperty($propertyName);

            return 
                !$property->isPublic() && !$property->isStatic()
                    ? ($this->getAttrInstance(Setter::class, $propertyName) !== null)
                    : true
            ;
        }

        
        public function isPropertyGettable(string $propertyName): bool {
            $property = $this->hasProperty($propertyName) ? $this->getProperty($propertyName) : null;

            return 
                ($property ? (!$property->isPublic() && !$property->isStatic()) : true)
                    ? ((
                        $this->getAttrInstance(Getter::class, $propertyName)
                        ?? $this->getAttrInstance(SetOnce::class, $propertyName)
                    ) !== null)
                    : true
            ;
        }
    }
}

?>