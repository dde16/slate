<?php

namespace Slate\Neat\Attribute {

    use Attribute;
    use Slate\Metalang\MetalangAttribute;

    #[Attribute(Attribute::TARGET_PROPERTY)]
    class ReadOnly extends MetalangAttribute { }
}

?>