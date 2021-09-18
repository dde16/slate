<?php

namespace Slate\Utility {
    trait TImitate {
        use TUninstantiable;
        use TSingleton;

        /**
         * A method to create an instance for this class.
         * 
         * @return object
         */
        protected abstract static function createInstance(): object;

        /**
         * The magic method that will, if any methods are called statically, try and 
         * call the singleton instance. This can create loops if not used carefully.
         * 
         * @return mixed
         */
        protected static function __callStatic(string $name, array $arguments): mixed {
            return static::getInstance()->{$name}(...$arguments);
        }
    }
}

?>