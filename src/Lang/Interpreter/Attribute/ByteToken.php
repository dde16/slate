<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter\Attribute {
    use Attribute;

    #[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
    class ByteToken extends LiteralToken {
        public function __construct(int|array $bytes) {
            parent::__construct(
                \Arr::join(
                    \Arr::map(
                        \Arr::always($bytes),
                        fn(int|string $byte): string => is_int($byte) ? chr($byte) : $byte
                    )
                )
            );
        }
    }
}

?>