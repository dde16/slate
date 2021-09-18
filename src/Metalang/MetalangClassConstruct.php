<?php

namespace Slate\Metalang {
    use Slate\Utility\TWrap;
    use Reflector;

    class MetalangClassConstruct {
        use TWrap;

        public const AROUND = "construct";
    
        protected object $construct;
        protected object $parent;
    
        public function __construct(object $parent, Reflector $construct) { 
            $this->parent    = $parent;
            $this->construct = $construct;
        }
    }
}

?>