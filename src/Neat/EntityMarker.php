<?php

namespace Slate\Neat {
    use Slate\Structure\Enum;

    /**
     * Used to manually mark instances for a given action
     * and is carried out when an Entity::{action} is made.
     */
    class EntityMarker extends Enum {
        public const DEFAULT     = 0;
        public const UPSERT      = 1;
        public const DELETE      = 2;
    }
}

?>