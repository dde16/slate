<?php declare(strict_types = 1);

namespace Slate\Neat\Attribute {
    use Attribute;
    use Slate\Metalang\Prefab\MetalangNamedAttribute;
    use Slate\Neat\EntityStaticCarry;

#[Attribute(Attribute::TARGET_METHOD)]
    class Scope extends MetalangNamedAttribute {
    }
}

?>