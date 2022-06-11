<?php declare(strict_types = 1);

namespace Slate\Foundation {
    class Provider {
        protected App $app;

        public function __construct($app) {
            $this->app = $app;
        }
    }
}

?>