<?php

namespace Slate\Neat\Attribute {

    use Attribute;
    use Slate\Metalang\MetalangAttribute;

    #[Attribute(Attribute::TARGET_METHOD)]
    class Throttle extends MetalangAttribute {
        protected float $throttle;

        public function __construct(float $throttle) {
            $this->throttle = $throttle;
        }

        public function getThrottle(): float {
            return $this->throttle;
        }
    }
}

?>