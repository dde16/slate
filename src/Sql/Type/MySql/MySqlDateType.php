<?php

namespace Slate\Sql\Type\MySql {
    use Slate\Sql\Type\SqlDateType;

    class MySqlDateType extends SqlDateType {
        public const FORMAT = "Y-m-d";
    }
}

?>