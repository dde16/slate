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

    public static function call(string|array|object $function, array $arguments = []): mixed {
        return call_user_func_array(
            $function,
            $arguments
        );
    }
}