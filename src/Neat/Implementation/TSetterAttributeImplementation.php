<?php

namespace Slate\Neat\Implementation {

    use Closure;
    use Slate\Metalang\Attribute\AttributeSet;
    use Slate\Neat\Attribute\Setter;

trait TSetterAttributeImplementation {
        #[AttributeSet(Setter::class)]
        public function setterImplemetor(string $name, mixed $value, object $next): void {
            $design = static::design();

            if(($setter = $design->getAttrInstance([Setter::class, Property::class], $name)) !== null) {
                $args = [$value, $setter->getFor()];

                if(\Cls::isSubclassInstanceOf($setter, Property::class))
                    $args = [$value, true, $setter->getFor()];

                $setter->parent->invokeArgs($this, $args);
            }
            else {
                $next($name, $value);
            }
            
        }
    }
}

?>