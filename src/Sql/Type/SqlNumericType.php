<?php

namespace Slate\Sql\Type {

    use DateTime;

    abstract class SqlNumericType extends SqlType implements ISqlTypeBackwardConvertable, ISqlTypeForwardConvertable {
        protected ?int $precision = null;
        protected ?int $scale = null;
        protected bool $unsigned;

        public function fromArray(array $array): void {
            parent::fromArray($array);
            
            $this->scale     = $array["scale"] !== null     ? \Integer::tryparse($array["scale"])     : null;
            $this->precision = $array["precision"] !== null ? \Integer::tryparse($array["precision"]) : 0;
            $this->size      = 8;
        }

        public function getPrecision(): int|null {
            return $this->precision;
        }

        public function getScale(): int|null {
            return $this->scale;
        }

        public function isSigned(): bool {
            return !$this->unsigned;
        }

        public function toSqlValue(mixed $value): string {
            if(is_string($value)) {}
            else if(is_object($value) ? \Cls::isSubclassInstanceOf($value, DateTime::class) : false) {
                $value = $value->getTimestamp();
            }
            else if(is_int($value)) {
                $value = \Str::val($value);
            }
            else if(is_bool($value)) {
                $value = intval($value);
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
        
        public function fromSqlValue(string $value, string $target): mixed {
            switch($target) {
                case \Str::class:
                    break;
                case \Boolean::class:
                    $value = \Integer::tryparse($value) > 0;
                    break;
                case \Integer::class:
                    $value = \Integer::tryparse($value);
                    break;
                case \Real::class:
                    $value = \Real::tryparse($value);
                    break;
                default:
                    if($target === DateTime::class || is_subclass_of($target, DateTime::class)) {
                        $datetime = new DateTime();
                        $datetime->setTimestamp(\Integer::tryparse($value));

                        $value = $datetime;
                    }
                    else {
                        throw new \Error(\Str::format(
                            "Cannot convert {} sql type to {}",
                            $this->datatype,
                            !\Cls::exists($target) ? $target::NAMES[0] : $target
                        ));
                    }
                    
                    break;
            }

            return $value;
        }
    }
}

?>