<?php

namespace Slate\Sql\Type\MySql {
    use Slate\Sql\Type\SqlCharacterTextType;

    class MySqlTinyTextType extends SqlCharacterTextType {
        protected int $size = 256;
    }
}

?>