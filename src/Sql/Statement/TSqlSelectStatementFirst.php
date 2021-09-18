<?php

namespace Slate\Sql\Statement {
    use Slate\Facade\DB;

    trait TSqlSelectStatementFirst {
        public function first(string $conn = null) {
            $wrapper = DB::select()->from(clone $this, as: "anon")->limit(1);
        
            return $wrapper->get()->current();
        }
    }
}

?>