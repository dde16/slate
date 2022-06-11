<?php

namespace Slate\Sql\Contract {
    interface ISqlable {
        public function toSql(): ?string;
        function buildSql(): ?array;
    }
}

?>