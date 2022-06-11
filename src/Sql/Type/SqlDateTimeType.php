<?php declare(strict_types = 1);

namespace Slate\Sql\Type {

    use Slate\Exception\ParseException;
    use DateTime;
    use Error;
    use PDOException;

    class SqlDateTimeType extends SqlType implements ISqlTypeBackwardConvertable, ISqlTypeForwardConvertable {
        public const FORMAT = "Y-m-d H:i:s";
        public const FORMAT_EXTENDED = "Y-m-d H:i:s\.u";
        public const DATE   = TRUE;
        public const TIME   = TRUE;

        protected ?int $precision;
        
        public function fromArray(array $array): void {
            parent::fromArray($array);

            if($array["precision"] !== null) {
                $this->precision = \Integer::tryparse($array["precision"]);
            }
            else {
                $this->precision = 0;
            }



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
                $seconds = intval($value - $subseconds);

                $datetime->setTimestamp($seconds);

                $datetime->setTime(
                    \Integer::tryparse($datetime->format("H")), 
                    \Integer::tryparse($datetime->format("i")), 
                    \Integer::tryparse($datetime->format("s")),
                    $subseconds
                );
            }
            else if(is_object($value) ? \Cls::isSubclassInstanceOf($value, DateTime::class) : false) {
                $datetime = $value;
            }
            else {
                $datetime = null;
            }

            if($datetime) {
                return $datetime->format($this->precision > 0 ? static::FORMAT_EXTENDED : static::FORMAT);
            }
            
            throw new \Error(\Str::format(
                "Unsupported conversion type from {} to {}.",
                \Any::getType($value),
                $this->datatype
            ));
        }

        public function fromSqlValue(string $value, string $target): mixed {
            $datetime = (new DateTime());

            if($this->precision > 0) {
                $datetime = $datetime->createFromFormat(static::FORMAT_EXTENDED, substr($value, 0, -2));
            }
            else {
                $datetime = $datetime->createFromFormat(static::FORMAT, $value);
            }

            if($datetime === false)
                throw new PDOException("Unable to parse datetime string '$value'.");

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

        public function buildSql(): array {
            return [
                $this->datatype
            ];
        }
    }
}

?>