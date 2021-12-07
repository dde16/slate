<?php

namespace Slate\Neat\Implementation {

    use Closure;
    use Slate\Exception\UndefinedRoutineException;
    use Slate\Metalang\Attribute\HookCall;
    use Slate\Metalang\Attribute\HookCallStatic;
    use Slate\Utility\TMacroable;
    use Slate\Utility\TMacros;

    trait TMacroableImplementation {

        use TMacros;

        #[HookCall("Macro")]
        public function macroObjectImplementor(string $name, array $arguments, object $next): array {
            return static::macroSharedImplementor(
                $name,
                $arguments,
                $this,
                $next
            );
        }

        #[HookCallStatic("Macro")]
        public static function macroStaticImplementor(string $name, array $arguments, object $next): array {
            return static::macroSharedImplementor(
                $name,
                $arguments,
                null,
                $next
            );
        }
        
        public static function macroSharedImplementor(
            string $name,
            array $arguments,
            object|null $context,
            object $next
        ): array {
            if(($macro = @static::$macros[static::class][$name]) !== null)
                return [true, $context ? $macro->call($context, ...$arguments) : $macro(...$arguments)];

            return $next($name, $arguments);
        }
    }
}

?>