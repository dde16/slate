<?php declare(strict_types = 1);

namespace Slate\Sql {
    interface ISqlProvidesResults {
        function get();
        function take(int $amount);
        function chunk(int $size, int $from = 0);
    }
}

?>