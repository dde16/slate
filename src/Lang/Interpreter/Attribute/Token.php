<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter\Attribute {
    use Attribute;

    use Slate\Metalang\MetalangAttribute;
    use Slate\Data\Iterator\StringIterator;
    use Slate\IO\File;
    use Slate\Lang\Interpreter\ICodeable;

    #[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
    abstract class Token extends MetalangAttribute {
        public function getKeys(): string|array {
            return strval($this->parent->getValue());
        }

        public function getTrackingLevel(): ?string {
            return $this->tracklevel;
        }

        public function getTrackingCount(): ?string {
            return $this->trackcount;
        }
    }

}

?>