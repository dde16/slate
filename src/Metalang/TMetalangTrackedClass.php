<?php

namespace Slate\Metalang {
    trait TMetalangTrackedClass {

        public static function design(): MetalangTrackedDesign {
            return parent::design();
        }

        public function __construct() {
            parent::__construct();
            static::design()->addInstance($this);
        }

        public function __destruct() {
            static::design()->removeInstance($this);
        }
    }
}

?>