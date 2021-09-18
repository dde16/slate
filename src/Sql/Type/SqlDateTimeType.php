<?php

namespace Slate\Sql\Type {

    use Slate\Exception\ParseException;
    use DateTime;

    class SqlDateTimeType extends SqlType implements ISqlTypeBackwardConvertable, ISqlTypeForwardConvertable {
        public const FORMAT = "Y-m-d H:i:s";
        public const DATE   = TRUE;
        public const TIME   = TRUE;

        protected int $precision;
        
        public function fromArray(array $array): void {
            parent::fromArray($array);
            
            // $this->precision = \Integer::tryparse($array["precision"]);

            $this->size = strlen((new DateTime())->format(static::FORMAT));
        }

        public function getScalarType(): string {
            return \Str::class;
        }

        public function toSqlValue(mixed $value): string {
            $datetime = (new DateTime());

            if(is_string($value))
                return $value;

            if(is_int($value)) {
                $datetime->setTimestamp($value);
            }
            else if(is_float($value)) {
                $subseconds = $value % 1;
                $seconds = $value - $subseconds;

                $datetime->setTimestamp($seconds);

                $datetime->setTime(
                    $datetime->format("H"), 
                    $datetime->format("i"), 
                    $datetime->format("s"),
                    $subseconds
                );
            }
            else if(is_object($value) ? \Cls::isSubclassInstanceOf($value, DateTime::class) : false) {
                $datetime = $value;
            }
            else {
                $datetime = null;
            }

            if($datetime)
                return $datetime->format(static::FORMAT);
            
            throw new \Error(\Str::format(
                "Unsupported conversion type from {} to {}.",
                \Any::getType($value),
                $this->datatype
            ));
        }

        public function fromSqlValue(string $value, string $target): mixed {
            $datetime = (new DateTime());
            $datetime = $datetime->createFromFormat(static::FORMAT, $value);

            if(static::DATE === FALSE)
                $datetime->setDate(1970, 1, 1);

            if(static::TIME === FALSE)
                $datetime->setTime(0, 0, 0, 0);

            switch($target) {
                case \Str::class:
                    break;
                case \Integer::class:
                    $value = $datetime->getTimestamp();
                    break;
                case \Real::class:
                    $value = \Real::tryparse($datetime->format("U\.u"));
                    break;
                default:
                    if(\Cls::isSubclassInstanceOf($target, DateTime::class) || $target === DateTime::class) {
                        $value = $datetime;
                    }
                    else {
                        throw new ParseException("Unable to convert SQL type {$this->datatype} to type {$target}.");
                    }
                    break;
            }
            
            return $value;
        }
    }
}

?>