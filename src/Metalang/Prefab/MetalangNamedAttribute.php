<?php declare(strict_types = 1);

namespace Slate\Metalang\Prefab {

    use Slate\Metalang\MetalangDesign;
    use Slate\Metalang\MetalangAttribute;

    class MetalangNamedAttribute extends MetalangAttribute {
        protected string $name;

        public function __construct(string $name) {
            $this->name = $name;
        }

        public function getKeys(): string|array {
            return $this->getName();
        }

        public function getName(): string {
            return $this->name;
        }
    }
}

?>