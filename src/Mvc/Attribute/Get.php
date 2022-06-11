<?php declare(strict_types = 1);

namespace Slate\Mvc\Attribute {

use ReflectionMethod;
use Slate\Metalang\MetalangDesign;

class Get extends Route {
        public function __construct(string|array $cache = null, bool $requiresAuth = false) {
            parent::__construct("get", $cache, $requiresAuth);
        }
    }
}

?>