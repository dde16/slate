<?php

namespace Slate\Metalang {
    class MetalangClassConstructAttributable extends MetalangClassConstruct {
        public function getAllAttributes(bool $sort = true): array {
            $attributes = $this->getAttributes();
            
            if($sort) {
                $classes = \Arr::mapAssoc(
                    \Arr::map(
                        $attributes,
                        function($attribute) {
                            return $attribute->getName();
                        }
                    ),
                    function($index, $attribute) {
                        return [$attribute, []];
                    }
                );
    
                foreach($attributes as $attribute) {
                    $classes[$attribute->getName()][] = $attribute;
                }
    
                return $classes;
            }
            
            return $attributes;
        }
    
        public function getAttributes(string $name = null, int $flags = 0): array {
            return \Arr::map(
                parent::getAttributes($name, $flags),
                function($attribute) {
                    return(new MetalangClassConstructAttribute($this, $attribute));
                }
            );
        }
    }
}

?>