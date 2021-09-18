<?php

namespace Slate\Sql {
    interface ISqlResultProvider {
        function get();
        function take(int $amount);
        function chunk(int $size, int $from = 0);
    }
}

?>