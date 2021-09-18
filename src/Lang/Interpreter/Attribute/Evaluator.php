<?php

namespace Slate\Lang\Interpreter\Attribute {
    use Attribute;

    use Slate\Metalang\Prefab\MetalangNamedAttribute;
    use Slate\Data\Iterator\StringIterator;
    use Slate\IO\File;

    #[Attribute(Attribute::TARGET_METHOD)]
    class Evaluator extends MetalangNamedAttribute {
        protected string $name;

        public function __construct(string $name) {
            $this->name = $name;
        }

        public function getKeys(): string|array {
            return $this->name;
        }
    }
}

?>