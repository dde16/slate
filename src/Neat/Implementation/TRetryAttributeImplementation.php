<?php

namespace Slate\Neat\Implementation {

    use Closure;
    use Slate\Metalang\Attribute\AttributeCallStatic;
    use Slate\Metalang\Attribute\AttributeCall;
    use Slate\Neat\Attribute\Retry;
    use Slate\Neat\Attribute\Throttle;

trait TRetryAttributeImplementation {
        #[AttributeCall(Retry::class, [Throttle::class])]
        public function retryImplementor(string $name, array $arguments, object $next): mixed {
            return static::retrySharedImplementor(
                $name,
                $arguments,
                function(string $name, array $arguments) {
                    return $this->{$name}(...$arguments);
                },
                $next
            );
        }

        #[AttributeCallStatic(Retry::class, [Throttle::class])]
        public static function retryStaticImplementor(string $name, array $arguments, object $next): mixed {
            return static::retrySharedImplementor(
                $name,
                $arguments,
                function(string $name, array $arguments) {
                    return static::{$name}(...$arguments);
                },
                $next
            );
        }

        public static function retrySharedImplementor(string $name, array $arguments, Closure $call, object $next): mixed {
            $design = static::design();
    
            if(($retryAttribute = $design->getAttrInstance(Retry::class, $name)) !== null) {
                $methodName = $retryAttribute->parent->getName();
                
                $count = 1;
                $continue  = true;
                


                while($continue) {
                    list($match, $result) = $next($name, $arguments);

                    if(!$match)
                        $result = $call($name, $arguments);

                    if($result !== true)
                        if($count++ >= $retryAttribute->getBackoff())
                            $continue = false;
                        else
                            sleep($retryAttribute->getDelay());
                    else
                        $continue = false;
                }
    
                return [true, $result];
            }
            
            return ($next)($name, $arguments);
        }
    }
}

?>