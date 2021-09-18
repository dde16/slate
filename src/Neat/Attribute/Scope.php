<?php

namespace Slate\Neat\Attribute {
    use Attribute;
    use Slate\Metalang\Prefab\MetalangNamedAttribute;
    use Slate\Neat\EntityStaticCarry;
    use Slate\Utility\TMiddleware;

#[Attribute(Attribute::TARGET_METHOD)]
    class Scope extends MetalangNamedAttribute {
        use TMiddleware;

        protected static array $middleware = [
            "instance.carry" => null::class,
            "static.carry"   => EntityStaticCarry::class,
        ];

        protected static array $using = [
            "instance.carry" => null,
            "static.carry"   => EntityStaticCarry::class,
        ];
    }
}

?>