<?php

namespace Slate\Metalang\Attribute {
    use Attribute;

    #[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
    class AttributeSet extends AttributeImplementor {
        public const NAME = "AttributeSet";
    }
}

?>