<?php declare(strict_types = 1);

namespace Slate\Sql {

    use Slate\Data\Contract\IAnyForwardConvertable;
    use Slate\Data\Contract\IStringForwardConvertable;
    use Slate\Data\TStringNativeForwardConvertable;
    use Slate\Facade\Sql;
    use Slate\Sql\Contract\ISqlable;

    class SqlReference implements IStringForwardConvertable {
        use TStringNativeForwardConvertable;

        public mixed $reference = null;
        public ?string $as        = null;

        public function __construct(string|IStringForwardConvertable|ISqlable $reference) {
            $this->reference = $reference;
        }

        public function as(string $as): static {
            $this->as = $as;

            return $this;
        }

        public function toString(): string {
            return (is_object($this->reference) ? Sql::sqlify($this->reference) : $this->reference). ($this->as !== null ? " AS " . $this->as : "");
        }
    }
}

?>