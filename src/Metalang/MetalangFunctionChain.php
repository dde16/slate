<?php

namespace Slate\Metalang {
    class MetalangFunctionChain extends MetalangFunctionStructure {
        public function __invoke(): mixed {
            $arguments = func_get_args();
    
            return (array_pop($this->closures) ?? $this->finally)(...[...$arguments, $this]);
        }
    }
}

?>