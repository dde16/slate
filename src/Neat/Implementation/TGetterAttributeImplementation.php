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

            if(($getter = $design->getAttrInstance(Getter::class, $name)) !== null) {
                list($match, $result) = $next($name);


                if(!$match) {
                    $result = $getter->parent->invokeArgs($this, [$getter->getFor()]);
                }

                return [true, $result];
            }
            
            return $next($name);
        }
    }
}

?>