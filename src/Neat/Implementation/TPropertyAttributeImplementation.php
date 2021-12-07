<?php

namespace Slate\Neat\Implementation {
    use Slate\Metalang\Attribute\HookCall;
    use Slate\Neat\Attribute\Getter;
    use Slate\Neat\Attribute\Property;
    use Slate\Neat\Attribute\Setter;

    trait TPropertyAttributeImplementation {
        #[HookCall(Property::class)]
        public function propertyGetImplemetor(string $name, array $arguments, object $next): array {
            $design = static::design();

            if($design->getAttrInstance(Property::class, $name) !== null) {
                $nextAttributeClass = count($arguments) === 0 ? Getter::class : Setter::class;

                if(($nextAttribute = $design->getAttrInstance($nextAttributeClass, $name)) !== null) {
                    return [true, $nextAttribute->parent->invokeArgs($this, $arguments)];
                }
                else if($nextAttributeClass === Getter::class) {
                    return [true, $this->{$name}];
                }
                else if($nextAttributeClass === Setter::class) {
                    $this->{$name} = $arguments[0];

                    return [true, null];
                }
            }

            return $next($name, $arguments);
        }
    }
}

?>