<?php

namespace Slate\Neat\Attribute {

    use Attribute;

    #[Attribute(Attribute::TARGET_METHOD)]
    class ThrottleMethod extends Throttle {
        
    }
}

?>