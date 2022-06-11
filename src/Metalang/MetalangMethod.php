<?php declare(strict_types = 1);

namespace Slate\Metalang {
    class MetalangMethod extends MetalangClassConstructAttributable {
        public function getParameters(): array {
            return \Arr::map(
                $this->construct->getParameters(),
                function($parameter) {
                    return (new MetalangClassConstructAttributable(
                        $this,
                        $parameter
                    ));
                }
            );
        }
    }
}

?>