<?php

namespace Slate\Metalang {

    use Slate\Metalang\Attribute\AttributeCall;
    use Slate\Metalang\Attribute\AttributeGet as AttributeGetImplementor;
    use Slate\Metalang\Attribute\AttributeSet as AttributeSetImplementor;
    use Slate\Metalang\Attribute\AttributeCall as AttributeCallImplementor;
    use Slate\Metalang\Attribute\AttributeCallStatic as AttributeCallStaticImplementor;

    abstract class MetalangClass {
        public const DESIGN = MetalangDesign::class;

        public function __construct() {
            if(!\Cls::getConstant(static::class, "JIT", false)) 
                static::design();
        }

        public static function design(): MetalangDesign {
            return MetalangDesign::of(static::class);
        }

        public function __call(string $name, array $arguments): mixed {
            $design = static::design();

            $attributes  = $design->getAttrInstances(AttributeCallImplementor::class);
            $entrypoints = $design->getImplementorCache(AttributeCallImplementor::class);

            foreach($entrypoints as $entrypoint) {
                $graph =  new MetalangImplementorFunctionGraph(
                    $entrypoint->getTargetAttribute(),
                    $attributes,
                    function($attribute, $arguments) {
                        return $attribute->parent->invokeArgs($this, $arguments);
                    },
                    function() {
                        return [false, null];
                    }
                );

                list($match, $result) = $graph($name, $arguments);

                if($match) 
                    return $result;
            }

            throw new \Error(\Str::format(
                "Call to undefined method {}::{}().",
                static::class, $name
            ));
        }
    
        public static function __callStatic(string $name, array $arguments): mixed {
            $design = static::design();

            $attributes = $design->getAttrInstances(AttributeCallStaticImplementor::class);
            $entrypoints = $design->getImplementorCache(AttributeCallStaticImplementor::class);

            foreach($entrypoints as $entrypoint) {
                $graph =  new MetalangImplementorFunctionGraph(
                    $entrypoint->getTargetAttribute(),
                    $attributes,
                    function($attribute, $arguments) {
                        return $attribute->parent->invokeArgs(null, $arguments);
                    },
                    function() {
                        return [false, null];
                    }
                );

                list($match, $result) = $graph($name, $arguments);

                if($match) 
                    return $result;
            }

            throw new \Error(\Str::format(
                "Call to undefined method {}::{}().",
                static::class, $name
            ));
        }
    
        public function __get(string $name): mixed {
            $design = static::design();

            $attributes = $design->getAttrInstances(AttributeGetImplementor::class);
            $entrypoints = $design->getImplementorCache(AttributeGetImplementor::class);

            foreach($entrypoints as $entrypoint) {
                $graph =  new MetalangImplementorFunctionGraph(
                    $entrypoint->getTargetAttribute(),
                    $attributes,
                    function($attribute, $arguments) {
                        return $attribute->parent->invokeArgs($this, $arguments);
                    },
                    function() {
                        return [false, null];
                    }
                );

                list($match, $result) = $graph($name);

                if($match) 
                    return $result;
            }

            if($design->hasProperty($name, \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE))
                throw new \Error(
                    \Str::format(
                        "Unable to access enclosed property {}::\${}",
                        static::class, $name
                    )
                );
            
            return $this->{$name};
        }
    
        public function __set(string $name, mixed $value): void {
            $design = static::design();

            $attributes = $design->getAttrInstances(AttributeSetImplementor::class);
            $entrypoints = $design->getImplementorCache(AttributeSetImplementor::class);

            foreach($entrypoints as $entrypoint) {
                $graph =  new MetalangImplementorFunctionGraph(
                    $entrypoint->getTargetAttribute(),
                    $attributes,
                    function($attribute, $arguments) {
                        return $attribute->parent->invokeArgs($this, $arguments);
                    },
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