<?php

namespace Slate\Sql {

    use Slate\Data\IStringForwardConvertable;

    class SqlReference {
        public string|IStringForwardConvertable|null $reference = null;
        public ?string $as        = null;

        public function __construct(string|IStringForwardConvertable $reference) {
            $this->reference = $reference;
        }

        public function as(string $as): static {
            $this->as = $as;

            return $this;
        }

        public function toString(): string {
            return (
                \Any::isObject($this->reference)
                    ? (\Cls::isSubclassInstanceOf($this->reference, SqlConstruct::class)
                        ? \Str::wrapc($this->reference->toString(), "()")
                        : $this->reference->toString()
                    )
                    : $this->reference
            ). ($this->as !== null ? " AS " . $this->as : "");
        }
    }
}

?>