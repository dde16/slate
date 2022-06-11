<?php declare(strict_types = 1);

namespace Slate\Neat\Implementation {
    use Closure;
    use Slate\Metalang\Attribute\HookCallStatic;
    use Slate\Metalang\Attribute\HookCall;
    use Slate\Facade\App;
    use Slate\Neat\Attribute\Cache;
    use Slate\Neat\Attribute\Throttle;

trait TCacheAttributeImplementation {
        #[HookCallStatic(Cache::class, [Throttle::class])]
        public static function cacheStaticImplementor(string $name, array $arguments, object $next): mixed {
            return static::cacheSharedImplementor($name, $arguments, function($name, $arguments) {
                return static::{$name}(...$arguments);
            }, $next);
        }

        #[HookCall(Cache::class, [Throttle::class])]
        public function cacheImplementor(string $name, array $arguments, object $next): mixed {
            return static::cacheSharedImplementor($name, $arguments, function($name, $arguments) {
                return $this->{$name}(...$arguments);
            }, $next);
        }

        public static function cacheSharedImplementor(string $name, array $arguments, Closure $miss, object $next): array {
            $design = static::design();
    
            if(($cacheAttribute = $design->getAttrInstance(Cache::class, $name)) !== null) {
                $repo = App::repo($cacheAttribute->getRepo());
                $cacheKey = $cacheAttribute->getCacheKey();
    
                if($repo->expired($cacheKey)) {
                    $result   = null;
                    $match = false;

                    list($match, $result) = $next($name, $arguments);
                    
                    if($match === false) {
                        $result = $miss($name, $arguments);
                    }

                    $repo->put(
                        $cacheKey,
                        $result,
                        $cacheAttribute->getTtl()
                    );
                }
                else {
                    $result = $repo->pull($cacheKey);
                }
    
                return [true, $result];
            }
            
            return ($next)($name, $arguments);
        }

    }
}

?>