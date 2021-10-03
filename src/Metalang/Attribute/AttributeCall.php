<?php

namespace Slate\Metalang\Attribute {
    use Attribute;

    #[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
    class AttributeCall extends AttributeImplementor {
        public const NAME = "AttributeCall";
    }
}

?>