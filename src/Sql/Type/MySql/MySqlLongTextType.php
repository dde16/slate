<?php declare(strict_types = 1);

namespace Slate\Sql\Type\MySql {
    use Slate\Sql\Type\SqlCharacterTextType;

    class MySqlLongTextType extends SqlCharacterTextType {
        protected int $size = 4_294_967_295;
    }
}

?>