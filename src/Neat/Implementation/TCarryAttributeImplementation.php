<?php

namespace Slate\Neat\Implementation {

    use Closure;
    use Slate\Metalang\Attribute\HookCallStatic;
    use Slate\Metalang\Attribute\HookCall;
    use Slate\Neat\Attribute\Carry;

trait TCarryAttributeImplementation {
        #[HookCall(Carry::class)]
        public function implementInitialInstanceCarry(string $name, array $arguments, object $next): array {
            return static::implementInitialSharedCarry(
                $name,
                $arguments,
                function($attribute) {
                    $carrier = ($attribute::class)::use("instance.carry");

                    $instance = &$this;
                    $state = new ($carrier)($attribute->new(static::class), $instance);

                    return $state;
                },
                function(string $name, array $arguments) {
                    return $this->{$name}(...$arguments);
                },
                $next
            );
        }

        #[HookCallStatic(Carry::class)]
        public static function implementInitialStaticCarry(string $name, array $arguments, object $next): array {
            return static::implementInitialSharedCarry(
                $name,
                $arguments,
                function($attribute) {
                    $carrier = ($attribute::class)::use("static.carry");
                    $state = new ($carrier)($attribute->new(static::class), static::class);

                    return $state;
                },
                function(string $name, array $arguments) {
                    return  static::{$name}(...$arguments);
                },
                $next
            );
        }

        public static function implementInitialSharedCarry(string $name, array $arguments, Closure $create, Closure $call, object $next): mixed {
            $design = static::design();

            if(($carryAttribute = $design->getAttrInstance(Carry::class, $name)) !== null) {
                $method = $carryAttribute->parent->getName();

                $state = $create($carryAttribute);

                // if(\Cls::hasInterface($state->getPrimary(), ICarryAcknowledge::class)) {
                //     $state->getPrimary()->acknowledgeCarry($carryAttribute);
                // }

                $return = $call($method, [$state->getPrimary(), ...$arguments]);

                return [true, $return ?: $state];
            }

            return $next($name, $arguments);
        }
        
        // Leading Calls
        public function implementLeadingInstanceCarry(string $name, array $arguments, object $state): mixed {
            return static::implementLeadingSharedCarry(
                $name,
                $arguments,
                function(string $name, array $arguments) { 
                    return $this->{$name}(...$arguments);
                },
                $state
            );
        }

        
        // Leading Calls
        public static function implementLeadingStaticCarry(string $name, array $arguments, object $state): mixed {
            return static::implementLeadingSharedCarry(
                $name,
                $arguments,
                function(string $name, array $arguments) { 
                    return static::{$name}(...$arguments);
                },
                $state
            );
        }

        public static function implementLeadingSharedCarry(string $name, array $arguments, Closure $call, object $state): mixed{
            $design = static::design();

            if(($carryAttribute = $design->getAttrInstance(Carry::class, $name)) !== null) {
                // if(\Cls::hasInterface($state->getPrimary(), ICarryAcknowledge::class)) {
                //     $state->getPrimary()->acknowledgeCarry($carryAttribute);
                // }
                
                $return = $call($carryAttribute->parent->getName(), [$state->getPrimary(), ...$arguments]);
                
                return $return ?: $state;
            }

            return null;
        }
    }
}

?>