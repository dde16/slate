<?php declare(strict_types = 1);

namespace Slate\Sql\Type {

    use DateTime;

    class SqlNumericRealType extends SqlNumericType {

        public function toSqlValue(mixed $value): string {
            if(is_string($value)) {}
            else if(is_object($value) ? \Cls::isSubclassInstanceOf($value, DateTime::class) : false) {
                $value = \Real::fromDateTime($value);
            }
            else if(is_float($value)) {
                $value = \Str::val($value);
            }
            else if(is_bool($value)) {
                $value = floatval($value);
            }
            else {
                throw new \Error(\Str::format(
                    "Unable to convert {} to sql type {}",
                    gettype($value),
                    $this->datatype
                ));
            }

            return $value;
        }

        public function getScalarType(): string {
            return \Real::class;
        }
    }
}

?>