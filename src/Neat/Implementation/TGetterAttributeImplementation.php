<?php

namespace Slate\Neat\Implementation {
    use Slate\Metalang\Attribute\HookGet;
    use Slate\Neat\Attribute\Computed;
    use Slate\Neat\Attribute\Getter;
    use Slate\Neat\Attribute\GetterOf;
    use Slate\Neat\Attribute\Property;
    use Slate\Neat\Attribute\ReadOnly;

    trait TGetterAttributeImplementation {
        #[HookGet(Getter::class)]
        #[HookGet(ReadOnly::class)]
        public function getterImplemetor(string $name, object $next): mixed {
            $design = static::design();

            if(($readonly = $design->getAttrInstance(ReadOnly::class, $name)) !== null) {
                list($match, $result) = $next($name);

                if(!$match)
                    $result = $this->{$readonly->parent->getName()};

                return [true, $result];
            }
            else if(($getter = $design->getAttrInstance(Getter::class, $name)) !== null) {
                list($match, $result) = $next($name);

                $args = [$getter->getFor()];

                if(!$match) {
                    $result = $getter->parent->invokeArgs($this, $args);
                }

                return [true, $result];
            }
            
            return $next($name);
        }
    }
}

?>