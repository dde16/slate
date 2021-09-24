<?php

namespace Slate\Neat\Implementation {
    use Slate\Metalang\Attribute\AttributeGet;
    use Slate\Neat\Attribute\Computed;
    use Slate\Neat\Attribute\Getter;
    use Slate\Neat\Attribute\GetterOf;

trait TGetterAttributeImplementation {
        
        #[AttributeGet(Getter::class)]
        public function getterImplemetor(string $name, object $next): mixed {
            $design = static::design();

            if(($getterAttribute = $design->getAttrInstance(Getter::class, $name)) !== null) {
                list($match, $result) = $next($name);

                $args = [$name];

                if(!$match) {
                    $result = $getterAttribute->parent->invokeArgs($this, $args);
                }

                return [true, $result];
            }
            
            return $next($name);
        }
    }
}

?>