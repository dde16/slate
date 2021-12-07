<?php

namespace Slate\Sql\Statement {
    trait TSqlInsertStatement {
        public static function insert(array $rows = []): SqlInsertStatement {
            $stmt = (new SqlInsertStatement());


            return
                !\Arr::isEmpty($rows)
                    ? $stmt->{
                        \Arr::any($rows, fn($v) => is_array($v))
                            ? 'rows'
                            : 'row'
                        }($rows)
                    : $stmt;
        }
    }
}

?>