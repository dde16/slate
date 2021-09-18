<?php

namespace Slate\Sql\Type {
    abstract class SqlType {
        protected string $datatype;
        protected int    $size;
        public mixed  $default;

        public function getDataType(): string {
            return $this->datatype;
        }

        public function fromArray(array $array): void {
            $this->datatype = $array["datatype"];
            $this->default = $array["default"];

        }

        /**
         * Get the (maximum) size of the current type in native types.
         */
        public function getSize(): int {
            return $this->size;
        }

        public function hasDefault(): bool {
            return $this->default !== null;
        }

        public function getDefault(string $target): mixed {
            return ($this->default !== "NULL" && $this->default !== null)
                ? (
                    \Cls::implements($this, ISqlTypeBackwardConvertable::class)
                        ? $this->fromSqlValue($this->default, $target)
                        : $this->default
                )
                : null;
        }


        /**
         * Get the native type recommendation for this type.
         * 
         * For example, BIGINT will exceed the native integer capacity.
         * So it should be stored as a string.
         */
        public abstract function getScalarType(): string;
    }
}

?>