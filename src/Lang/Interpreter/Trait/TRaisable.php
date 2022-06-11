<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter\Trait {

    use Slate\Exception\ParseException;
    use Slate\Lang\Interpreter\Attribute\Token;

    trait TRaisable {
        public function raiseEof(): void {
            throw new \Error("Unexpected EOF.");
        }

        public function raiseNonMatch(): void {
            throw new ParseException(
                \Str::format(
                    "Unexpected token '{}' at {}. ",
                    $this->code->current(),
                    $this->code->tell()
                )
            );
        }
    }
}

?>