<?php

namespace Slate\Foundation {
    use Attribute;
    use Slate\Metalang\MetalangAttribute;

    #[Attribute(Attribute::TARGET_METHOD)]
    class Stager extends MetalangAttribute {
        protected int $flag;

        public function __construct(int $flag) {
            $this->flag = $flag;
        }

        public function is(int $flag): bool {
            return $this->flag ===  $flag;
        }

        public function getFlag(): int {
            return $this->flag;
        }
    }
}

?>