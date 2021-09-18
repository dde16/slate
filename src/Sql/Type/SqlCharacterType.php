<?php

namespace Slate\Sql\Type {
    class SqlCharacterType extends SqlType implements ISqlTypeForwardConvertable, ISqlTypeBackwardConvertable {

        protected int    $octetLength;
        protected int    $charLength;
        protected int    $bytesPerChar;
                
        public function fromArray(array $array): void {
            parent::fromArray($array);
            
            $this->charLength = \Integer::tryparse($array["charLength"]);
            $this->octetLength = \Integer::tryparse($array["octetLength"]);

            $this->bytesPerChar = (int)($this->octetLength / $this->charLength);

            $this->size = $this->charLength;

            $this->default = $array["default"];
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
                if(($value = json_encode($value)) === false) {
                    throw new \Error("Unable to json encode array/object.");
                }
            }

            if(is_string($value)) {
                if((mb_strlen($value, "utf-8") * $this->bytesPerChar) > ($this->octetLength)) {
                    throw new \Error("Unable to convert string to sql value as it exceeds the maximum length.");
                }
            }
            else {
                $value = \Str::val($value);
            }

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
    }
}

?>