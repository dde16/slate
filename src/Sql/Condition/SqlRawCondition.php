<?php declare(strict_types = 1);

namespace Slate\Sql\Condition {

    use Slate\Sql\Contract\ISqlable;
    use Stringable;

    class SqlRawCondition extends SqlBaseCondition {
        public string|object $value;

        public function toString(): ?string {
            $value = $this->value;

            if($value instanceof ISqlable) {
                $value = \Str::wrapc($value->toSql(), "()");
            }
            else if($value instanceof Stringable) {
                $value = $value->__toString();
            }

            return !empty($value) ? $value : null;
        }
    }
}

?>