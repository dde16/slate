<?php

namespace Slate\Metalang\Attribute {
    use Attribute;

    #[Attribute(ATTRIBUTE::TARGET_METHOD)]
    class AttributeGet extends AttributeImplementor {
        public const NAME = "AttributeGet";
    }
}

?>