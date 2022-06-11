<?php declare(strict_types = 1);

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
            $macro = @static::$macros[static::class][$name];

            if($macro === null) {
                $class = \Arr::first(array_keys(static::$macros), fn(string $class): bool => is_subclass_of(static::class, $class));

                if($class !== null) {
                    $macro = static::$macros[$class][$name];
                }
            }

            if($macro !== null)
                return [true, $context ? $macro->call($context, ...$arguments) : $macro(...$arguments)];

            return $next($name, $arguments);
        }
    }
}

?>