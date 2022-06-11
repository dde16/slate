<?php declare(strict_types = 1);

namespace Slate\Structure {
    class ProceduralArray {
        use TProceduralArray;
        protected array $values;
    
        public function __construct() {
            $this->values       = [];
            $this->refs         = [&$this->values];
        }
    
        public function toArray(): array {
            return $this->values;
        }
    }
}

?>