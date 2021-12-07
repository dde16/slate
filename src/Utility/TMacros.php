<?php

namespace Slate\Utility {

    use Closure;

    trait TMacros {
        /**
         * Undocumented variable
         *
         * @var array<string,array<string,Closure>>
         */
        private static array $macros = [];

        /**
         * Register a macro.
         *
         * @param string $name
         * @param Closure $closure
         *
         * @return void
         */
        public static function macro(string $name, Closure $closure): void {
            $for = static::class;

            if(\Arr::hasKey(@static::$macros[self::class] ?? [], $name)) 
                $for = self::class;

            static::$macros[$for][$name] = $closure;
        }

        /**
         * Check whether a macro by the given name exists.
         *
         * @param string $name
         *
         * @return boolean
         */
        public static function hasMacro(string $name): bool {
            return \Arr::hasKey(@static::$macros[static::class] ?? [], $name) || \Arr::hasKey(@static::$macros[self::class] ?? [], $name); 
        }

        /**
         * Define a mcro if it has not already been defined already.
         *
         * @param string $name
         * @param Closure $closure
         *
         * @return void
         */
        public static function contingentMacro(string $name, Closure $closure): void {
            if(!static::hasMacro($name))
                static::macro($name, $closure);
        }
    }
}

?>