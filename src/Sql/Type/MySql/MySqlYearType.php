<?php declare(strict_types = 1);

namespace Slate\Sql\Type\MySql {
    use Slate\Sql\Type\SqlDateType;

    class MySqlYearType extends SqlDateType {
        public const FORMAT = "Y";

        public function fromSqlValue(string $value, string $target): mixed {
            if($target === \Integer::class)
                return \Integer::tryparse($value);
            
            return parent::fromSqlValue($value, $target);
        }
    }
}

?>