<?php

namespace Slate\Data {
    interface IJitStructureItem {
        function consumeAncestors(array $parents): void;
    }
}

?>