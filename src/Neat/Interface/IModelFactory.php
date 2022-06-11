<?php

namespace Slate\Neat\Interface {
    interface IModelFactory {
        static function factory(object $model): static;
    }
}

?>