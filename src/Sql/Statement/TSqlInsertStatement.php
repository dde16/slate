<?php

namespace Slate\Sql\Statement {
    trait TSqlInsertStatement {
        public static function insert(array $rows = []): object {
            $stmt = (new SqlInsertStatement());


            return !\Arr::isEmpty($rows) ? $stmt->{\Arr::any($rows, function($v){ return is_array($v);} ) ? 'rows' : 'row'}($rows) : $stmt;
        }
    }
}

?>