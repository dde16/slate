<?php

namespace Slate\Neat\Implementation {

    use Closure;
    use Slate\Metalang\Attribute\AttributeCallStatic;
    use Slate\Metalang\Attribute\AttributeCall;
    use Slate\Neat\Attribute\Benchmark;
    use Slate\Neat\Attribute\Cache;
    use Slate\Neat\Attribute\Retry;

trait TBenchmarkAttributeImplementation {
        #[AttributeCallStatic(Benchmark::class, [Retry::class, Cache::class])]
        public static function benchmarkStaticImplementor(string $name, array $arguments, object $next): mixed {
            return static::benchmarkSharedImplementor(
                $name, $arguments,
                function(string $name, array $arguments) {
                    return static::{$name}(...$arguments);
                },
                $next
            );
        }

        #[AttributeCall(Benchmark::class, [Retry::class, Cache::class])]
        public function benchmarkInstanceImplementor(string $name, array $arguments, object $next): mixed {
            return static::benchmarkSharedImplementor(
                $name, $arguments,
                function(string $name, array $arguments) {
                    return $this->{$name}(...$arguments);
                },
                $next
            );
        }

        public static function benchmarkSharedImplementor(string $name, array $arguments, Closure $call, object $next): array {
            $design = static::design();

            if(($benchmarkAttribute = $design->getAttrInstance(Benchmark::class, $name)) !== null) {
                
                $startTime = microtime(true);
                
                list($match, $result) = $next($name, $arguments);

                $elapsedTime = microtime(true) - $startTime;

                if(!$match) {
                    $startTime = microTime(true);

                    $result = $call($name, $arguments);
                    
                    $elapsedTime = microtime(true) - $startTime;
                }

                $benchmarkAttribute->pipe($elapsedTime);

                return [true, $result];
            }

            
            return ($next)($name, $arguments);
        }
    }
}

?>