<?php

namespace Slate\Neat\Attribute {
    use Attribute;
    use Slate\Metalang\MetalangAttribute;
    
    #[Attribute(Attribute::TARGET_METHOD)]
    class Retry extends MetalangAttribute {
        public const NAME = "Retry";
    
        protected float $delay;
        protected int   $backoff;
    
        public function __construct(float $delay, int $backoff = 2) {
    
            if($delay <= 0)
                throw new \Error("Delay must be a non-zero, positive, real number.");
    
            $this->delay   = $delay;
            
            if($backoff < 1)
                throw new \Error("Backoff must be a non-zero, positive, integer.");
            
            $this->backoff = $backoff;
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