<?php

namespace Slate\Utility {
    trait TUninstantiable {
        protected function __construct() {}
        protected function __clone() {}
        // protected function __wakeup() {}
    }
}

?>