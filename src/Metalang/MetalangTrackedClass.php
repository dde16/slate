<?php

namespace Slate\Metalang {
    class MetalangTrackedClass extends MetalangClass {
        use TMetalangTrackedClass;

        public const DESIGN = MetalangTrackedDesign::class;
    }
}

?>