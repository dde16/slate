<?php declare(strict_types = 1);

namespace Slate\Neat\Attribute {

    use Attribute;
    use Slate\Metalang\MetalangAttribute;
    use Slate\Metalang\MetalangDesign;

    abstract class Throttle extends MetalangAttribute {
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