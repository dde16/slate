<?php

namespace Slate\Utility {
    trait TMiddleware {
        // protected static array $middleware;
        // protected static array $using;

        public static function usable(string $name): bool {
            return \Arr::hasKey(static::$middleware, $name);
        }

        public static function use(string $name): string {
            return static::$using[$name];
        }

        public static function tap(string $name, string $class = null): void {
            if($class === null) {
                $class = $name;

                if(\Cls::exists($class)) {
                    $names = \Arr::keys(\Arr::filter(static::$middleware, function($middleware) use($class) {
                        return \Cls::isSubclassOf($class, $middleware);
                    }));

                    $count = count($names);

                    if($count === 1) {
                        $name = $names[0];
                    }
                    else if($count > 1) {
                        throw new Error(\Str::format(
                            "Multiple names arose for tapping of middleware class '{}', you must explicitly clarify a name.",
                            $class
                        ));
                    }
                    else {
                        throw new BadFunctionCallException(\Str::format(
                            "Middleware class '{}' is not allowed as a derivative.", $class
                        ));
                    }
                }
                else {
                    throw new BadFunctionCallException(\Str::format(
                        "Middleware class '{}' does not exist.", $class
                    ));
                }
            }

            if(static::usable($name)) {                
                if(\Cls::exists($class)) {
                    static::$using[$name] = $class;
                }
                else {
                    throw new BadFunctionCallException(\Str::format(
                        "Middleware class '{}' does not exist.", $class
                    ));
                }
            }
            else {
                throw new \Error(\Str::format(
                    "{} does not utilise middleware by the name '{}'",
                    static::class, $name
                ));
            }
        }
    }
}

?>