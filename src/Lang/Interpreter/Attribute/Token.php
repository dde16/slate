<?php

namespace Slate\Lang\Interpreter\Attribute {
    use Attribute;

    use Slate\Metalang\MetalangAttribute;
    use Slate\Data\Iterator\StringIterator;
    use Slate\IO\File;
    use Slate\Lang\Interpreter\ICodeable;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
    abstract class Token extends MetalangAttribute {
        public function getKeys(): string|array {
            return $this->parent->getValue();
        }

        public function getTrackingLevel(): string|null {
            return $this->tracklevel;
        }

        public function getTrackingCount(): string|null {
            return $this->trackcount;
        }
    }

}

?>