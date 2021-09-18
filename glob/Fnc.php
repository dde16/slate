<?php
abstract class Fnc {
    /**
     * Chain functions by injecting the next function as the last argument, but unlike
     * chain; the current function can determine the successive function by its name.
     * 
     * @param Closure[] $callables
     * @param mixed[]   $initials
     * @param Closure   $fallback
     * 
     * @return mixed
     */
    public static function graph(array $callables, array $initials, Closure $fallback): mixed {
        if(!\Arr::isEmpty($callables)) {
            $chain = new Slate\Metalang\MetalangFunctionGraph($callables, $fallback);
    
            return $chain(...$initials);
        }
    
        return $fallback ? $fallback(...$initials) : null;
    }
    

    /**
     * Chain functions by injecting the next function as the last argument,
     * as the classic middleware style.
     * 
     * @param Closure[] $callables   All the functions to be chained
     * @param array     $intiials    The initial arguments to pass to the first function call
     * @param Closure   $callback This function to be called if the function chain reaches its end (if the last function is called)
     * 
     * @return mixed
     */
    public static function chain(
        array $callables,
        mixed $initials = [],
        Closure $fallback = null,
        bool $escape = false
    ): mixed {
        if(!\Arr::isEmpty($callables)) {

            $wrappers = [];

            $callables = \Arr::values(\Arr::map(
                $callables,
                function($callable) use($fallback, $escape) {
                    return(new Slate\Metalang\MetalangFunctionChainLink($callable, $escape ? $fallback : null));
                }
            ));

            $callables[] = !$escape ? $fallback : null;

            foreach(\Arr::lead($callables) as list($lastWrapper, $nextWrapper)) {
                $lastWrapper->next = $nextWrapper;

                $wrappers[] = $lastWrapper;
            }

            unset($callables);

            return $wrappers[0](...$initials);
        }

        return $fallback ? $fallback(...$initials) : null;
    }

    public static function exists(string|array $function): bool{
        return is_string($function) ? function_exists($function) : method_exists(...$function);
    }

    //TODO: review usage and remove
    public static function true(): bool {
        return true;
    }

    //TODO: review usage and remove
    public static function false(): bool {
        return false;
    }

    //TODO: review usage and remove
    public static function return(): mixed {
        return function($any) { return $any; }; 
    }

    //TODO: review usage and change to 'bind'
    public static function inject(callable $function, array $additionals = []): Closure {
        return function (/* ...arguments */) use ($function, $additionals) {
            return \call_user_func_array(
                $function,
                [...\func_get_args(), ...$additionals]
            );
        };
    }

    //TODO: review usage and remove
    public static function alias(callable $function, int|array $pass = null): Closure {
        return function (/* ...arguments */) use ($function, $pass) {
            $arguments = \func_get_args();

            if(is_int($pass)) {
                $arguments = \Arr::slice($arguments, 0, $pass);
            }

            return \call_user_func_array(
                $function,
                $arguments
            );
        };
    }

    public static function call(string|array|object $function, array $arguments = []): mixed {
        return call_user_func_array(
            $function,
            $arguments
        );
    }

    //TODO: review usage and remove
    public static function not(callable|string $function): Closure {
        return function($argument) use($function) {
            return !\Fnc::call($function, [$argument]);
        };
    }

    //TODO: review usage and remove
    public static function equals($value, bool $strict = false): Closure {
        return function($argument) use($value, $strict) {
            return(($strict) ? ($value == $argument) : ($value === $argument));
        };
    }

    public static function cast(string $typeName): mixed {
        $typeClass = type($typeName);

        if(\Cls::isSubclassOf($typeClass, \ScalarType::class)) {
            return function($value) use ($typeClass) {
                return $typeClass::parse($value);
            };
        }
        else {
            throw new \UnexpectedValueException(Str::format("Type {}<{}> is not scalar, thus not supported.", $typeClass, $typeName));
        }
    }
}