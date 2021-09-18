<?php

namespace Slate\Neat\Attribute {
    use Attribute;

    use Slate\Metalang\MetalangAttribute;

    #[Attribute(Attribute::TARGET_ALL)]
    class Ignore extends MetalangAttribute {
        public const NAME = "Ignore";

        public array $for;

        public function __construct(string ...$for) {
            $this->for = $for;
        }

        public function getFor(): array {
            return $this->for;
        }
    }
}

?>