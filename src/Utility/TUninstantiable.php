<?php declare(strict_types = 1);

namespace Slate\Utility {
    trait TUninstantiable {
        protected function __construct() {}
        protected function __clone() {}
        // protected function __wakeup() {}
    }
}

?>