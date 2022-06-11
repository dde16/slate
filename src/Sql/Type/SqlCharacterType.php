<?php declare(strict_types = 1);

namespace Slate\Sql\Type {
    use Slate\Sql\Clause\TSqlCharacterSetClause;
    use Slate\Sql\Clause\TSqlCollateClause;

    class SqlCharacterType extends SqlType implements ISqlTypeForwardConvertable, ISqlTypeBackwardConvertable {
        
        use TSqlCharacterSetClause;
        use TSqlCollateClause;

        protected ?int    $octetLength = null;
        protected ?int    $charLength = null;
        protected ?int    $bytesPerChar = null;
                
        public function fromArray(array $array): void {
            parent::fromArray($array);
            
            if($array["charLength"] !== null) {
                $this->charLength = \Integer::tryparse($array["charLength"]);
                $this->size = $this->charLength;
            }
            
            if($array["octetLength"] !== null) {
                $this->octetLength = \Integer::tryparse($array["octetLength"]);
            }

            if($this->charLength !== null && $this->octetLength !== null) {
                $this->bytesPerChar = (int)($this->octetLength / $this->charLength);
            }

            $this->default = $array["default"];
        }

        public function len(int $charLength): static {
            $this->charLength = $charLength;
            $this->size = $charLength;

            return $this;
        }

        public function getDefault(string $target): mixed {
            $default = ($this->default !== "NULL" && !is_null($this->default))
                ? (
                    \Cls::implements($this, ISqlTypeBackwardConvertable::class)
                        ? $this->fromSqlValue(\Str::removeAffix($this->default, "'"), $target)
                        : \Str::removeAffix($this->default, "'")
                )
                : null;


            return $default;
        }

        public function fromSqlValue(string $value, string $target): mixed {
            switch($target) {
                case \Arr::class:
                case \Obj::class:
                    return \Json::tryparse($value);
                    break;

                default:
                    return $target::tryparse($value);
                    break;
            }
        }

        public function toSqlValue(mixed $value): string {
            if(\Any::isCompound($value)) {
                $value = \Json::tryEncode($value);
            }

            if(is_string($value) ? (mb_strlen($value, "utf-8") * $this->bytesPerChar) > ($this->octetLength) : false)
                throw new \Error("Unable to convert string to sql value as it exceeds the maximum length.");

            $value = \Str::val($value);

            return $value;
        }

        public function getScalarType(): string {
            return \Str::class;
        }

        public function getOctetLength(): int {
            return $this->octetLength;
        }

        public function getCharLength(): int {
            return $this->charLength;
        }

        public function getBytesPerChar(): int {
            return $this->bytesPerChar;
        }

        public function buildSql(): array {
            return [
                $this->datatype . ($this->charLength !== null ? "({$this->charLength})" : ""),
            ];
        }
    }
}

?>