<?php

namespace Slate\Neat\Implementation {

    use Closure;
    use Slate\Metalang\Attribute\HookCallStatic;
    use Slate\Metalang\Attribute\HookCall;
    use Slate\Neat\Attribute\Alias;
    use Slate\Neat\Attribute\Benchmark;

trait TAliasAttributeImplementation {
        #[HookCallStatic(Alias::class, [Benchmark::class])]
        public static function aliasStaticImplementor(string $name, array $arguments, object $next): mixed {
            return static::aliasSharedImplementor(
                $name,
                $arguments,
                "static.",
                function(string $name, array $arguments): mixed {
                    return static::{$name}(...$arguments);
                },
                $next
            );
        }

        #[HookCall(Alias::class, [Benchmark::class])]
        public function aliasImplementor(string $name, array $arguments, object $next): mixed {
            return static::aliasSharedImplementor(
                $name,
                $arguments,
                "",
                function(string $name, array $arguments): mixed {
                    return $this->{$name}(...$arguments);
                },
                $next
            );
        }

        public static function aliasSharedImplementor(string $name, array $arguments, string $modifier, Closure $call, object $next): mixed {
            $design = static::design();
            
            if(($aliasAttribute = $design->getAttrInstance(Alias::class, $modifier.$name)) !== null) {
                $name = $aliasAttribute->parent->getName();

                list($match, $value) = $next($name, $arguments);

                return
                    ($match === false)
                        ? [true, $call($name, $arguments)]
                        : [$match, $value];
            }
            
            return ($next)($name, $arguments);
        }
    }
}

?>