<?php

namespace Slate\Neat\Attribute {

    use Attribute;
    use ReflectionProperty;
    use Slate\Metalang\MetalangAttribute;
    use Slate\Metalang\MetalangDesign;

    #[Attribute(Attribute::TARGET_PROPERTY)]
    class Fillable extends MetalangAttribute {    }
}

?>