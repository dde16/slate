<?php

namespace Slate\Neat\Implementation {

    use Closure;
    use Slate\Metalang\Attribute\HookSet;
    use Slate\Neat\Attribute\Setter;

trait TSetterAttributeImplementation {
        #[HookSet(Setter::class)]
        public function setterImplemetor(string $name, mixed $value, object $next): void {
            $design = static::design();

            if(($setter = $design->getAttrInstance(Setter::class, $name)) !== null) {
                $args = [$value, $setter->getFor()];

                $setter->parent->invokeArgs($this, $args);
            }
            else {
                $next($name, $value);
            }
        }
    }
}

?>