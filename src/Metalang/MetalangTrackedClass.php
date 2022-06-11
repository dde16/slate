<?php declare(strict_types = 1);

namespace Slate\Metalang {
    class MetalangTrackedClass extends MetalangClass {
        use TMetalangTrackedClass;

        public const DESIGN = MetalangTrackedDesign::class;
    }
}

?>