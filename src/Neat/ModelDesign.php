<?php

namespace Slate\Neat {

    use Slate\Metalang\MetalangDesign;
    use Slate\Metalang\MetalangTrackedDesign;
    use Slate\Neat\Attribute\Fillable;
    use Slate\Neat\Attribute\Getter;
    use Slate\Neat\Attribute\ReadOnly;
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
            $property = $this->getProperty($propertyName);

            return 
                !$property->isPublic() && !$property->isStatic()
                    ? ((
                        $this->getAttrInstance(Getter::class, $propertyName)
                        ?? $this->getAttrInstance(ReadOnly::class, $propertyName)
                    ) !== null)
                    : true
            ;
        }
    }
}

?>