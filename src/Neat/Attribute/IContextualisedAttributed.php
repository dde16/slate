<?php

namespace Slate\Neat\Attribute {
    interface IContextualisedAttribute {
        function getContext(): ?string;
        function inContext(?string $objectContext = null): bool;
    }
}

?>