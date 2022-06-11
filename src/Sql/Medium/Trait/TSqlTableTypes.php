<?php

namespace Slate\Sql\Medium\Trait {

    use Slate\Sql\SqlColumn;

    trait TSqlTableTypes {
        public function increments(string $name): SqlColumn {
            return $this->colum($name)->is("bigint unsigned")->increments();
        }
    }
}

?>