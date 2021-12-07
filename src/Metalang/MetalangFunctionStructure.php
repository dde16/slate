<?php

namespace Slate\Metalang {

    use Closure;

    class MetalangFunctionStructure {
        /**
         * A list of closures.
         *
         * @var Closure[]
         */
        public array $closures;

        /**
         * The Closure to run after the rest are complete.
         *
         * @var Closure
         */
        public Closure $finally;

        public function __construct(array $closures, Closure $finally) {
            $this->closures = $closures;
            $this->finally = $finally;
        }
    }
}

?>