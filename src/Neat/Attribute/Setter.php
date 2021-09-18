<?php

namespace Slate\Neat\Attribute {
    use Attribute;
    
    #[Attribute(Attribute::TARGET_METHOD)]
    class Setter extends Accessor {
        public const NAME = "Setter";
    }
}

?>