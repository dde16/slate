<?php
abstract class Fnc {
    /**
     * Chain a set of closures with the ability to jump further in the chain.
     *
     * @param array $closures
     * @param Closure $finally
     * @param Closure|null $handler
     * @param array $arguments
     * @param string|null $to
     *
     * @return mixed
     */
    public static function graph(array $closures, Closure $finally, Closure $handler = null, array $arguments = [], string $to = null): mixed {
        $handler ??= (fn(Throwable $throwable) => throw $throwable);
    
        if($to !== null) {
            $closure = $closures[$to];
    
            if($closure === null)
                throw new BadFunctionCallException("There is no next function or group named '{$to}'.");
    
            array_slice($closures, array_search($to, \Arr::keys($closures))+1);
        }
        else {
            $closure = \Arr::first($closures) ?: $finally;
            $closures = array_slice($closures, 1);
        }
    
        $next = fn(): mixed => \Fnc::graph($closures, $finally, $handler, func_get_args());
        $jump = fn(string $to, array $arguments): mixed => \Fnc::graph($closures, $finally, $handler, $arguments, $to);
    
        try {
            $arguments[] = $next;
            $arguments[] = $jump;

            $data = call_user_func_array($closure, $arguments);
        }
        catch(Throwable $throwable) {
            $data = $handler($throwable, $next, $jump);
        }
    
        return $data;
    }
    
    /**
     * Chain a set of closures, with the next injected as the last argument.
     *
     * @param Closure[] $closures
     * @param Closure $finally
     * @param array $arguments
     *
     * @return mixed
     */
    public static function chain(array $closures, array $arguments = []): mixed {
        $closure = \Arr::first($closures);
        $closures = array_slice($closures, 1);

        $arguments[] = fn() => \Fnc::chain($closures, func_get_args());
    
        /** Uses recursion to implement chaining */
        return $closure ? call_user_func_array($closure, \Arr::values($arguments)) : null;
    }
    
    /**
     * Check whether a function exists globally or on an object/class.
     *
     * @param string|array $function
     *
     * @return boolean
     */
    public static function exists(string|array $function): bool{
        return is_string($function) ? function_exists($function) : method_exists(...$function);
    }

    /**
     * call_user_func_array alias.
     *
     * @param string|array|object $function
     * @param array $arguments
     *
     * @return mixed
     */
    public static function call(string|array|object $function, array $arguments = []): mixed {
        return call_user_func_array(
            $function,
            $arguments
        );
    }

    public static function equals(mixed ...$tests): Closure {
        return fn($value) => \Arr::any($tests, (fn($test) => $value === $test));
    }

    public static function nequals(mixed ...$tests): Closure {
        return fn($value) => \Arr::all($tests, (fn($test) => $value !== $test));
    }
}
?>