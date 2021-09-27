<?php

namespace Slate\Neat\Implementation {

    use Closure;
    use Slate\Metalang\Attribute\AttributeSet;
    use Slate\Neat\Attribute\Setter;

trait TSetterAttributeImplementation {
        #[AttributeSet(Setter::class)]
        public function setterImplemetor(string $name, mixed $value, object $next): void {
            $design = static::design();

            if(($setter = $design->getAttrInstance(Setter::class, $name)) !== null) {
                $setter->parent->invokeArgs($this, [$value, $setter->getFor()]);
            }
            else {
                $next($name, $value);
            }
            
        }
    }
}

?>