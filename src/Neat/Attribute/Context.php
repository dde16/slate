<?php

namespace Slate\Neat\Attribute {

    use Attribute;
    use Slate\Metalang\Prefab\MetalangNamedAttribute;

    /**
     * This attribute will identify methods attributed to a common
     * context. But can return another if so desired.
     */
    #[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
    class Context extends MetalangNamedAttribute {
        
    }
}

?>