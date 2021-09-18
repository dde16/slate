<?php

namespace Slate\Mvc\Attribute {
    class Get extends Route {
        public function __construct(string|array $cache = null, bool $requiresAuth = false) {
            parent::__construct("get", $cache, $requiresAuth);
        }
    }
}

?>