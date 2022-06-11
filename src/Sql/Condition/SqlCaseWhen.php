<?php declare(strict_types = 1);

namespace Slate\Sql\Condition {
    use Slate\Facade\Sql;

    class SqlCaseWhen {
        protected object $parent;
        protected mixed  $then;

        public function __construct(object $parent) {
            $this->parent = $parent;
        }

        public function then(string|int|float|bool|object $then) {
            $this->then = $then;

            return $this->parent;
        }

        public function toString(): string{
            return "THEN " . Sql::sqlify($this->then);
        }
    }
}

?>