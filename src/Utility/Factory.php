<?php

namespace Slate\Utility {
    abstract class Factory {
        use TUninstantiable;

        public static function instantiate(string $class, $differentiator, array $arguments): object {
            return(new $class(...$arguments));
        }
    
        public static function create($differentiator, array $arguments = []): object {
            $class = static::match($differentiator);
    
            if($class !== NULL) {
                if(\Cls::exists($class)) {
                    return static::instantiate($class, $differentiator, $arguments);
                }
                else {
                    throw new \UnexpectedValueException(
                        \Str::format(
                            "Class '{}' does not exist.", $class
                        )
                    );
                }
            }
            else {
                throw new \Error(\Str::format(
                    "Unable to create from differentiator {}",
                    $differentiator
                ));
            }
        }
    
        public static function match($differentiator): string|null {
            return static::MAP[$differentiator];
        }
    }
}

?>