<?php

namespace Slate\Sql\Type {

    use DateTime;
    use Fnc;

    abstract class SqlNumericType extends SqlType implements ISqlTypeBackwardConvertable, ISqlTypeForwardConvertable {
        protected ?int $precision = null;
        protected ?int $scale = null;
        protected bool $unsigned = false;
        protected int $size = 8;

        public function fromArray(array $array): void {
            parent::fromArray($array);
            
            $this->scale     = $array["scale"] !== null     ? \Integer::tryparse($array["scale"])     : null;
            $this->precision = $array["precision"] !== null ? \Integer::tryparse($array["precision"]) : null;
        }

        public function signed(): static {
            $this->unsigned = false;

            return $this;
        }

        public function unsigned(): static {
            $this->unsigned = true;

            return $this;
        }

        public function precision(int $precision): static {
            $this->precision = $precision;

            return $this;
        }

        public function scale(int $scale): static {
            $this->scale = $scale;

            return $this;
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
                            !\class_exists($target) ? $target::NAMES[0] : $target
                        ));
                    }
                    
                    break;
            }

            return $value;
        }

        public function build(): array {
            $options = [$this->scale, $this->precision];
            
            if($options[0] === 0)
                $options[0] = null;

            $options = \Arr::filter($options);


            return [
                $this->datatype
                . (
                    !\Arr::isEmpty($options)
                        ? \Str::wrapc(\Arr::join($options, ","), "()")
                        : ""
                ),
                $this->unsigned ? "UNSIGNED" : null
            ];
        }
    }
}

?>