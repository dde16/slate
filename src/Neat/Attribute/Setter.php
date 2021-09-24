<?php

namespace Slate\Neat\Attribute {
    use Attribute;
    
    #[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
    class Setter extends Accessor {
        public const NAME = "Setter";
    }
}

?>