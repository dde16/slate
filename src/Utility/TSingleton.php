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
    }
}

?>