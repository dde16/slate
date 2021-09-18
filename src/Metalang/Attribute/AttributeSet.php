<?php

namespace Slate\Metalang\Attribute {
    use Attribute;

    #[Attribute(ATTRIBUTE::TARGET_METHOD)]
    class AttributeSet extends AttributeImplementor {
        public const NAME = "AttributeSet";
    }
}

?>