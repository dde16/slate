<?php declare(strict_types = 1);

namespace Slate\Sql\Type {
    interface ISqlTypeValidate {
        function validate($value);
    }
}

?>