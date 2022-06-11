<?php declare(strict_types = 1);

namespace Slate\Sql\Statement\Trait {

    use Slate\Sql\Statement\SqlInsertStatement;

    trait TSqlInsertStatement {
        public function insert(array $rows = []): SqlInsertStatement {
            $stmt = (new SqlInsertStatement($this));


            return
                !\Arr::isEmpty($rows)
                    ? $stmt->{
                        \Arr::any($rows, fn(mixed $v): bool => is_array($v))
                            ? 'rows'
                            : 'row'
                        }($rows)
                    : $stmt;
        }
    }
}

?>