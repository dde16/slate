<?php declare(strict_types = 1);

namespace Slate\Utility {

    use RuntimeException;
    use Slate\Exception\UndefinedRoutineException;

    abstract class Singleton {
        use TUninstantiable;
        use TMacroable {
            TMacroable::__callStatic as __macroCallStatic;
        }
    
        /**
         * The class to be a singleton of.
         * 
         * @var string
         */
        public const DEFAULT = NULL;

        /**
         * Tells the singleton whether it has to be manually initialised, not
         * automatically initialised through a method call.
         * 
         * @var bool
         */
        public const MANUAL = false;
    
        /**
         * Container to store the singleton classes.
         *
         * @var array<string,Singleton|string>
         */
        private static $singletons = [];
    
        /**
         * Container to store the singleton instances.
         * 
         * @var array<string,Singleton>
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
         * Tell whether the singleton has been instiantiated.
         * 
         * @return object
         */
        public static function instantiated(): bool {
            return @static::$instances[static::class] !== null;
        }

        /**
         * Get the singleton instance.
         *
         * @return object
         */
        public static function instance(bool $automatic = false): object {
            $instance = &static::$instances[static::class];

            if($instance === null) {
                if($automatic && static::MANUAL)
                    throw new RuntimeException("This Singleton must be built manually.");

                static::make();
            }

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
                return @self::$singletons[static::class] ?? static::DEFAULT ?? static::class;
            
            self::$singletons[static::class] = $class;
            return null;
        }
    
        public static function __callStatic(string $name, array $arguments) {
            try {
                return self::__macroCallStatic($name, $arguments);
            }
            catch(UndefinedRoutineException $exception) {
                return static::instance(true)->{$name}(...$arguments);
            }
        }
    }
}

?>
