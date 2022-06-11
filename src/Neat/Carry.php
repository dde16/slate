<?php declare(strict_types = 1);

namespace Slate\Neat {

    use Slate\Neat\Attribute\Carry as CarryAttribute;

    abstract class Carry {
        // The instance that is being carried.
        protected object $primary;

        public function __construct(object $primary) {
            $this->primary = $primary;
        }

        public function getPrimary(): object {
            return $this->primary;
        }

        public function allowInvoke(object $attribute, array $arguments): bool {
            return true;
        }

        public abstract function __call(string $method, array $arguments): mixed;
    }
}

?>