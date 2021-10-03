<?php

namespace Slate\Metalang\Attribute {
    use Attribute;

    #[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
    class AttributeGet extends AttributeImplementor {
        public const NAME = "AttributeGet";
    }
}

?>