<?php

namespace Slate\Neat\Implementation {

    use Closure;
    use Slate\Metalang\Attribute\AttributeCallStatic;
    use Slate\Metalang\Attribute\AttributeCall;
    use Slate\Neat\Attribute\Throttle;
    use Slate\Neat\Attribute\ThrottleContext;
    use Slate\Neat\Attribute\ThrottleMethod;

trait TThrottleAttributeImplementation {
        protected        array $objectLastMethodThrottle = [];
        protected static array $staticLastMethodThrottle = [];

        protected        ?float $objectLastContextThrottle = null;

        protected static array  $staticLastContextThrottle = [];

        #[AttributeCall(Throttle::class)]
        public function throttleImplementor(string $name, array $arguments, object $next): mixed {
            return static::throttleSharedImplementor(
                $name,
                $arguments,
                function(?float $throttle = null) {
                    if($throttle === null)
                        return $this->objectLastContextThrottle;

                    $this->objectLastContextThrottle = $throttle;
                },
                function(?float $throttle = null) use($name) {
                    if($throttle === null)
                        return @$this->objectLastThrottle[$name];

                    $this->objectLastThrottle[$name] = $throttle;
                },
                function(string $name, array $arguments) {
                    return $this->{$name}(...$arguments);
                },
                $next
            );
        }

        #[AttributeCallStatic(Throttle::class)]
        public static function throttleStaticImplementor(string $name, array $arguments, object $next): mixed {
            return static::throttleSharedImplementor(
                $name,
                $arguments,
                function(?float $throttle = null) {
                    if($throttle === null)
                        return @static::$staticLastContextThrottle[static::class];


                    static::$staticLastContextThrottle[static::class] = $throttle;
                },
                function(?float $throttle = null) use($name) {
                    if($throttle === null)
                        return @static::$staticLastMethodThrottle[static::class][$name];

                    static::$staticLastMethodThrottle[static::class][$name] = $throttle;
                },
                function(string $name, array $arguments) {
                    return static::{$name}(...$arguments);
                },
                $next
            );
        }

        public static function throttleSharedImplementor(
            string $name,
            array $arguments,
            Closure $lastContextThrottle,
            Closure $lastMethodThrottle,
            Closure $call,
            object $next
        ): mixed {
            $design = static::design();
    
            if(($throttleAttribute = $design->getAttrInstance(Throttle::class, $name)) !== null) {               

                if(\Cls::isSubclassInstanceOf($throttleAttribute, ThrottleContext::class)) {
                    $gsetter = $lastContextThrottle;
                }
                else if(\Cls::isSubclassInstanceOf($throttleAttribute, ThrottleMethod::class)) {
                    $gsetter = $lastMethodThrottle;
                }
                else {
                    throw new \Error(\Str::format(
                        "Throttle '{}' must derive from the ThrottleContext or ThrottleMethod class.",
                        get_class($throttleAttribute)
                    ));
                }
                
                $lastThrottle = $gsetter();

                if($lastThrottle !== null) {
                    $throttle = $throttleAttribute->getThrottle();
                    $now = microtime(true);
                    $lastThrottleSince = ($now - $lastThrottle) ;

                    if($lastThrottleSince < $throttle) {
                        if(time_sleep_until($now + ($throttle - $lastThrottleSince)) === false) {
                            throw new \Error();
                        }
                    }
                }

                $result = $call($name, $arguments);

                $gsetter(microtime(true));
    
                return [true, $result];
            }
            
            return ($next)($name, $arguments);
        }
    }
}

?>