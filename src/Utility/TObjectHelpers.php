<?php declare(strict_types = 1);

namespace Slate\Utility {

    use Closure;
    use stdClass;

trait TObjectHelpers {

        public static function hasProperty(string $name): bool {
            return property_exists(static::class, $name);
        }

        public static function hasMethod(string $name): bool {
            return method_exists(static::class, $name);
        }

        public function hasPublicMethod(string $name): bool {
            return \Obj::hasPublicMethod($this, $name);
        }

        public function hasPublicStaticMethod(string $name): bool {
            return \Cls::hasPublicMethod(static::class, $name);
        }

    }
}

?>