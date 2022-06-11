<?php declare(strict_types = 1);

namespace Slate\Sql\Type\MySql {
    use Slate\Sql\Type\SqlCharacterTextType;

    class MySqlMediumTextType extends SqlCharacterTextType {
        protected int $size = 16_777_215;
    }
}

?>