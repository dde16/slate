<?php declare(strict_types = 1);

namespace Slate\Sql\Condition {

    use Slate\Data\Contract\IStringForwardConvertable;
    use Slate\Facade\Sql;
    use Slate\Sql\SqlRaw;
    use Stringable;

    class SqlColumnCondition extends SqlBaseCondition {
        public string|Stringable $column;
        public string $operator;
        public mixed  $value;
    
        public function toString(): ?string {
            $column = (
                ($isStringable = (is_object($this->column)))
                    ? $this->column->__toString()
                    : $this->column
            );

            $value = (
                ($isStringable = (is_object($this->value)))
                    ? $this->value->toString()
                    : Sql::sqlify($this->value)
            );

            if(empty($value))
                return null;

            return "{$column} {$this->operator} " . (
                $isStringable && !($this->value instanceof SqlRaw)
                    ? \Str::wrapc($value, "()")
                    : $value
            );
        }

        public function buildSql(): array {
            return [ $this->logical, $this->toString() ];
        }
    }
}

?>