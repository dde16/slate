<?php

namespace Slate\Neat\Implementation {

    use Closure;
    use Slate\Metalang\Attribute\HookCallStatic;
    use Slate\Metalang\Attribute\HookCall;
    use Slate\Neat\Attribute\Column;
    use Slate\Neat\Entity;

trait TColumnAttributeImplementation {
        #[HookCallStatic(Column::class)]
        public static function columnStaticImplementor(string $name, array $arguments, object $next): mixed {
            $design = static::design();

            if($columnAttribute = $design->getAttrInstance(Column::class, $name)) {

                return [true, static::ref($columnAttribute->getColumnName(), Entity::REF_ITEM_WRAP)];
            }

            return ($next)($name, $arguments);
        }
    }
}

?>