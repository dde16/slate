<?php declare(strict_types = 1);

namespace Slate\Utility {
    use Closure;
    use Slate\Exception\UndefinedRoutineException;

    trait TMacroable {
        use TMacros;

        /**
         * Override object call magic method.
         *
         * @param string $name
         * @param array $arguments
         *
         * @return mixed
         */
        public function __call(string $name, array $arguments): mixed {
            if(($macro = @static::$macros[static::class][$name] ?? @static::$macros[self::class][$name]) !== null)
                return $macro->call($this, ...$arguments);

            throw new UndefinedRoutineException([static::class, $name], UndefinedRoutineException::ERROR_UNDEFINED_METHOD);
        }

        /**
         * Override static call magic method.
         *
         * @param string $name
         * @param array $arguments
         *
         * @return mixed
         */
        public static function __callStatic(string $name, array $arguments): mixed {
            if(($macro = @static::$macros[static::class][$name] ?? @static::$macros[self::class][$name]) !== null)
                return $macro(...$arguments);

            throw new UndefinedRoutineException([static::class, $name], UndefinedRoutineException::ERROR_UNDEFINED_METHOD);
        }
    }
}

?>