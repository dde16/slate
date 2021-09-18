<?php

namespace Slate\Metalang\Attribute {
    use Attribute;

    #[Attribute(ATTRIBUTE::TARGET_METHOD)]
    class AttributeCall extends AttributeImplementor {
        public const NAME = "AttributeCall";
    }
}

?>