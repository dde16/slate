<?php

namespace Slate\Neat\Implementation {

    use Closure;
    use Slate\Metalang\Attribute\AttributeCallStatic;
    use Slate\Metalang\Attribute\AttributeCall;
    use Slate\Neat\Attribute\Column;
    use Slate\Neat\Entity;

trait TColumnAttributeImplementation {
        #[AttributeCallStatic(Column::class)]
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