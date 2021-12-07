<?php

namespace Slate\Utility {

use ReflectionClass;

abstract class Factory {
        use TUninstantiable;

        public static function instantiate(string $class, $differentiator, array $arguments): object {
            return (new ReflectionClass($class))->newInstanceArgs($arguments);
        }
    
        public static function create($differentiator, array $arguments = []): object {
            $class = static::match($differentiator);
    
            if($class !== NULL) {
                if(\class_exists($class)) {
                    return static::instantiate($class, $differentiator, $arguments);
                }
                else {
                    throw new \UnexpectedValueException("Class '$class' does not exist.");
                }
            }
            else {
                throw new \Error("Unable to create from differentiator {$differentiator}.");
            }
        }
    
        public static function match($differentiator): string|null {
            return static::MAP[$differentiator];
        }
    }
}

?>