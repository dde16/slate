<?php

namespace Slate\Neat\Attribute {
    use Attribute;
    use Slate\Metalang\MetalangAttribute;

    #[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
    class Property extends MetalangAttribute { }
}

?>