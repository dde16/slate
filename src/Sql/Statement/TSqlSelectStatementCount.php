<?php declare(strict_types = 1);

namespace Slate\Sql\Statement {

use Slate\Facade\DB;

    trait TSqlSelectStatementCount {
        public function count(string ...$columns) {
            $alias = \Hex::encode(openssl_random_pseudo_bytes(16));

            $wrapper = DB::select(\Arr::isEmpty($columns) ? [
                "total" => "COUNT(1)"
            ] : \Arr::mapAssoc(
                $columns,
                function($key, $column) use($alias) {
                    return ["$column", "COUNT($alias.$column)"];
                }
            ))->from(clone $this, as: "`$alias`");

            return $wrapper->get()->current()["total"];
        }
    }
}

?>