<?php

namespace Slate\Neat\Attribute {
    use Attribute;
    
    #[Attribute(Attribute::TARGET_METHOD)]
    class Getter extends Accessor {
        public const NAME = "Getter";
    }
}

?>