<?php

namespace Slate\Neat\Implementation {

    use Closure;
    use Slate\Neat\ICarryAcknowledge;
    use Slate\Metalang\Attribute\HookCallStatic;
    use Slate\Metalang\Attribute\HookCall;
    use Slate\Neat\Attribute\Scope;
    use Slate\Neat\EntityCarryQuery;
    use Slate\Neat\EntityQueryStaticCarry;
    use Slate\Neat\EntitySimpleQuery;

trait TScopeAttributeImplementation {
        // // First call
        #[HookCall(Scope::class)]
        public function implementInitialInstanceScope(string $name, array $arguments, object $next): array {
            return static::implementInitialSharedScope(
                $name,
                $arguments,
                function()  {
                    $instance = &$this;
                    return (new (Scope::use("instance.carry"))(new EntityCarryQuery(static::class), $instance));
                },
                function(string $name, array $arguments) {
                    return $this->{$name}(...$arguments);
                },
                $next
            );
        }

        #[HookCallStatic(Scope::class)]
        public static function implementInitialStaticScope(string $name, array $arguments, object $next): array {
            return static::implementInitialSharedScope(
                $name,
                $arguments,
                function() {
                    return (new (Scope::use("static.carry"))(new EntityCarryQuery(static::class), static::class));
                },
                function(string $name, array $arguments) {
                    return static::{$name}(...$arguments);
                },
                $next
            );
        }

        public static function implementInitialSharedScope(string $name, array $arguments, Closure $create, Closure $call, object $next): mixed {
            $design = static::design();
            $relationship = $design->getAttrInstance([OneToOne::class, OneToMany::class], $name);


            $scope = $design->getAttrInstance(Scope::class, $name);

            if($relationship !== null || $scope !== null) {
                $state = $create();

                // if(\Cls::hasInterface($state->getPrimary(), ICarryAcknowledge::class)) {
                //     $state->getPrimary()->acknowledgeScope($scope);
                // }

                if($relationship === null && $scope !== null) {
                    $return = $call($scope->parent->getName(), [$state->getPrimary(), ...$arguments]);

                    return [true, $return ?: $state];
                }
                else if($relationship !== null) {
                    $state->getPrimary()->addChainLink($name);
                    $state->setClass($relationship->getForeignClass());

                    return [true, $state];
                }

            }

            return $next($name, $arguments);
        }
        
        // Leading Calls
        public function implementLeadingInstanceScope(string $name, array $arguments, object $state): mixed {
            return static::implementLeadingSharedScope(
                $name,
                $arguments,
                function(string $name, array $arguments) { 
                    return $this->{$name}(...$arguments);
                },
                $state
            );
        }

        // Leading Calls
        public static function implementLeadingStaticScope(string $name, array $arguments, object $state): mixed {
            return static::implementLeadingSharedScope(
                $name,
                $arguments,
                function(string $name, array $arguments) { 
                    return static::{$name}(...$arguments);
                },
                $state
            );
        }

        public static function implementLeadingSharedScope(string $name, array $arguments, Closure $call, object $state): mixed{
            $design = static::design();
            $relationship = $design->getAttrInstance([OneToOne::class, OneToMany::class], $name);

            if(($scope = $design->getAttrInstance(Scope::class, $name)) !== null) {
                $return = null;

                if($relationship === null) {
                    $return = $call($scope->parent->getName(), [$state->getPrimary(), ...$arguments]);
                }
                else {
                    $state->getPrimary()->addChainLink($name);
                }
                
                return $return ?: $state;
            }
            else if($relationship !== null) {
                $state->getPrimary()->addChainLink($name);

                return $state;
            }

            return null;
        }
    }
}

?>