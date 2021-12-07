<?php

namespace Slate\Utility {

    use Slate\Exception\UndefinedRoutineException;

    abstract class Singleton {
        use TUninstantiable;
        use TMacroable {
            TMacroable::__callStatic as __macroCallStatic;
        }
    
        public const DEFAULT = NULL;
    
        /**
         * Container to store the singleton classes.
         *
         * @var array<string,string>
         */
        private static $singletons = [];
    
        /**
         * Container to store the singleton instances.
         * 
         * @var array<string,object>
         */
        private static $instances = [];
    
        /**
         * Create an instance for the singleton.
         *
         * @return void
         */
        public static function make(array $arguments = []): object {
            return (self::$instances[static::class] = new (static::singleton())(...$arguments));
        }

        /**
         * Get the singleton instance.
         *
         * @return object
         */
        public static function instance(): object {
            $instance = &static::$instances[static::class];

            if($instance === null)
                static::make();

            return $instance;
        }
    
        /**
         * Register or get the singleton's class.
         *
         * @param string|null $class
         *
         * @return string|object
         */
        public static function singleton(string $class = null): ?string {
            if($class === null)
                return @self::$singletons[static::class] ?? static::DEFAULT;
            
            self::$singletons[static::class] = $class;
            return null;
        }
    
        public static function __callStatic(string $name, array $arguments) {
            try {
                return self::__macroCallStatic($name, $arguments);
            }
            catch(UndefinedRoutineException $exception) {
                return static::instance()->{$name}(...$arguments);
            }
        }
    }
}

?>