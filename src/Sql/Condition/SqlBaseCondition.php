<?php declare(strict_types = 1);

namespace Slate\Sql\Condition {

    use Slate\Structure\Struct;
    use Stringable;

    abstract class SqlBaseCondition extends Struct {
        public string  $logical;

        public function buildSql(): ?array {
            $value = $this->toString();

            return $value !== null ? [ $this->logical, $value ] : null;
        }
    }
}

?>