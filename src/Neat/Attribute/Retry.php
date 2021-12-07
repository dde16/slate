<?php

namespace Slate\Neat\Attribute {
    use Attribute;
    use ReflectionMethod;
    use Slate\Metalang\MetalangAttribute;
    use Slate\Metalang\MetalangDesign;
    
    #[Attribute(Attribute::TARGET_METHOD)]
    class Retry extends MetalangAttribute {    
        protected float $delay;
        protected int   $backoff;

        protected mixed $test;
    
        public function __construct(float $delay, int $backoff = 2, mixed $test = false, bool $strict = false) {
            if($delay <= 0)
                throw new \Error("Delay must be a non-zero, positive, real number.");
    
            $this->delay   = $delay;
            
            if($backoff < 1)
                throw new \Error("Backoff must be a non-zero, positive, integer.");
            
            $this->backoff = $backoff;

            $this->strict = $strict;
            $this->test = $test;
        }

        public function shouldBackOff(int $count): bool {
            return $count >= $this->backoff;
        }

        public function resultNonMatch(mixed $result): bool {
            return $this->strict ? ($result !== $this->test) : ($result != $this->test);
        }

        public function isStrict(): bool {
            return $this->strict;
        }

        public function getTest(): mixed {
            return $this->test;
        }
    
        public function getDelay(): float {
            return $this->delay;
        }
    
        public function getBackoff(): int {
            return $this->backoff;
        }
    }
}

?>