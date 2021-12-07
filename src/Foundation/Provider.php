<?php

namespace Slate\Foundation {
    class Provider {
        protected App $app;

        public function __construct($app) {
            $this->app = $app;
        }
    }
}

?>