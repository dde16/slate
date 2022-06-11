<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter\Attribute {
    use Attribute;
    use Slate\Data\Iterator\StringIterator;
    use Slate\IO\File;
    use Slate\Lang\Interpreter\ICodeable;

    #[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
    class RangeToken extends Token {
        protected int $from;
        protected int $to;

        public function __construct(int|string $from, int|string $to) {
            list($this->from, $this->to) = \Arr::map([$from, $to], function($chr) {
                return is_string($chr) ? ord($chr) : $chr;
            });
        }

        public function getFrom(): int {
            return $this->from;
        }

        public function getTo(): int {
            return $this->to;
        }
    }
}

?>