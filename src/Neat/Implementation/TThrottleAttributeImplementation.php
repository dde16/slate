<?php

namespace Slate\Neat\Implementation {

    use Closure;
    use Slate\Metalang\Attribute\AttributeCallStatic;
    use Slate\Metalang\Attribute\AttributeCall;
    use Slate\Neat\Attribute\Throttle;

    trait TThrottleAttributeImplementation {
        protected        array $objectLastThrottle = [];
        protected static array $staticLastThrottle = [];

        #[AttributeCall(Throttle::class)]
        public function throttleImplementor(string $name, array $arguments, object $next): mixed {
            return static::throttleSharedImplementor(
                $name,
                $arguments,
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
                function(?float $throttle = null) use($name) {
                    if($throttle === null)
                        return @static::$staticLastThrottle[static::class][$name];

                    static::$staticLastThrottle[static::class][$name] = $throttle;
                },
                function(string $name, array $arguments) {
                    return static::{$name}(...$arguments);
                },
                $next
            );
        }

        public static function throttleSharedImplementor(string $name, array $arguments, Closure $lastThrottleInterface, Closure $call, object $next): mixed {
            $design = static::design();
    
            if(($throttleAttribute = $design->getAttrInstance(Throttle::class, $name)) !== null) {
                $lastThrottle = $lastThrottleInterface();

                if($lastThrottle !== null) {
                    $throttle = $throttleAttribute->getThrottle();
                    $lastThrottleSince = (microtime(true) - $lastThrottle) ;

                    if($lastThrottleSince < $throttle)
                        sleep($throttle - $lastThrottleSince);
                }

                $result = $call($name, $arguments);

                $lastThrottleInterface(microtime(true));
    
                return [true, $result];
            }
            
            return ($next)($name, $arguments);
        }
    }
}

?>