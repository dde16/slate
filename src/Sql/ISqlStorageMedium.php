<?php

namespace Slate\Sql {
    interface ISqlStorageMedium {
        function drop(): void;
        function create(): void;
        function exists(): bool;
    }
}

?>