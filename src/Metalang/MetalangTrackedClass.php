<?php

namespace Slate\Metalang {
    class MetalangTrackedClass extends MetalangClass {
        public const DESIGN = MetalangTrackedDesign::class;

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