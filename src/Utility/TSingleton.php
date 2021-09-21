<?php

namespace Slate\Utility {
    trait TSingleton {
        use TUninstantiable;

        protected static ?object $instance = null;

        /**
         * A method to create an instance for this class.
         * 
         * @return object
         */
        protected static function createInstance():object {
            return(new static());
        }
        
        /**
         * The method to get the instance and create if it doesnt exist,
         * using the TSingleton::createInstance method.
         * 
         * @return object
         */
        public static function getInstance(): object {
            return (($instance = &static::$instance) === NULL ? $instance = static::createInstance() : $instance);
        }

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