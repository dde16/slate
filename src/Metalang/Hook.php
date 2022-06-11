<?php declare(strict_types = 1);

namespace Slate\Metalang {

    use ReflectionMethod;
    use Slate\Metalang\MetalangDesign;
    
    abstract class Hook extends MetalangAttribute {
        private static $primarykey = 0;
        protected string $currentKey;
        protected array  $nextKeys;
    
        public function __construct(string $key = null, array $next = []) {
            $this->currentKey = $key ?? $this->primarykey();
            $this->nextKeys   = $next;
        }

        private function primarykey(): int {
            return static::$primarykey++;
        }
            
        public function getKeys(): string|array {
            return $this->currentKey;
        }

        public function getNextKeys(): array {
            return $this->nextKeys;
        }
    }
}

?>