<?php declare(strict_types = 1);

namespace Slate\Neat\Attribute {

    use Attribute;
    use Slate\Metalang\MetalangAttribute;

    #[Attribute(Attribute::TARGET_PROPERTY)]
    class SetOnce extends MetalangAttribute { }
}

?>