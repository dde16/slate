<?php

namespace Slate\Metalang {

    use Slate\Exception\UndefinedRoutineException;
    use Slate\Metalang\Attribute\HookCall;
    use Slate\Metalang\Attribute\HookGet as HookGetImplementor;
    use Slate\Metalang\Attribute\HookSet as HookSetImplementor;
    use Slate\Metalang\Attribute\HookCall as HookCallImplementor;
    use Slate\Metalang\Attribute\HookCallStatic as HookCallStaticImplementor;
    
    trait TMetalangClass {
        public static function design(): MetalangDesign {
            return MetalangDesign::of(static::class);
        }

        public function __call(string $name, array $arguments): mixed {
            $design = static::design();

            /**
             * If a string starts with an underscore, remove it as
             * this indicates the function is called from within - 
             * thus maintain attribute functionality.
             */
            if(\Str::startswith($name, "_"))
                $name = \Str::removePrefix($name, "_");

            $attributes  = $design->getAttrInstances(HookCallImplementor::class);
            $entrypoints = $design->getHookCache(HookCallImplementor::class);

            foreach($entrypoints as $entrypoint) {
                $graph =  new MetalangHookGraph(
                    $entrypoint->getKeys(),
                    $attributes,
                    function(Hook $hook, array $arguments): mixed {
                        return $hook->parent->invokeArgs($this, $arguments);
                    },
                    fn(): array => [false, null]
                );

                list($match, $result) = $graph($name, $arguments);

                if($match) 
                    return $result;
            }

            throw new UndefinedRoutineException([static::class, $name], UndefinedRoutineException::ERROR_UNDEFINED_METHOD);
        }
    
        public static function __callStatic(string $name, array $arguments): mixed {
            $design = static::design();

            $attributes = $design->getAttrInstances(HookCallStaticImplementor::class);
            $entrypoints = $design->getHookCache(HookCallStaticImplementor::class);

            foreach($entrypoints as $entrypoint) {
                $graph =  new MetalangHookGraph(
                    $entrypoint->getKeys(),
                    $attributes,
                    function(Hook $hook, array $arguments): mixed {
                        return $hook->parent->invokeArgs(null, $arguments);
                    },
                    function() {
                        return [false, null];
                    }
                );

                list($match, $result) = $graph($name, $arguments);

                if($match) 
                    return $result;
            }

            throw new UndefinedRoutineException([static::class, $name], UndefinedRoutineException::ERROR_UNDEFINED_METHOD);
        }
    
        public function __get(string $name): mixed {
            $design = static::design();

            $attributes = $design->getAttrInstances(HookGetImplementor::class);
            $entrypoints = $design->getHookCache(HookGetImplementor::class);

            foreach($entrypoints as $entrypoint) {
                $graph =  new MetalangHookGraph(
                    $entrypoint->getKeys(),
                    $attributes,
                    function(Hook $hook, array $arguments) {
                        return $hook->parent->invokeArgs($this, $arguments);
                    },
                    fn(): array => [false, null]
                );

                list($match, $result) = $graph($name);

                if($match) 
                    return $result;
            }

            if($design->hasProperty($name, \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE)) {
                throw new \Error(
                    \Str::format(
                        "Unable to access enclosed property {}::\${}",
                        static::class, $name
                    )
                );
            }

            return $this->{$name};
        }
    
        public function __set(string $name, mixed $value): void {
            $design = static::design();

            $attributes = $design->getAttrInstances(HookSetImplementor::class);
            $entrypoints = $design->getHookCache(HookSetImplementor::class);

            foreach($entrypoints as $entrypoint) {
                $graph =  new MetalangHookGraph(
                    $entrypoint->getKeys(),
                    $attributes,
                    fn(Hook $hook, array $arguments): mixed => $hook->parent->invokeArgs($this, $arguments),
                    function(string $name, mixed $value)  use($design) {
                        if($design->hasProperty($name, \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE))
                            throw new \Error(
                                \Str::format(
                                    "Unable to set enclosed property {}::\${}",
                                    static::class, $name
                                )
                            );

                        $this->{$name} = $value;
                    }
                );

                $graph($name, $value);
            }
        }
    }
}

?>