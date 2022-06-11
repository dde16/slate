<?php declare(strict_types = 1);

namespace Slate\Neat {
    class EntityStaticCarry extends StaticCarry {
        public function __call(string $name, array $arguments): mixed {
            return \Cls::hasMethod($this->primary, $name)
                ? $this->primary->{$name}(...$arguments)
                : ($this->class)::implementLeadingStaticScope($name, $arguments, $this);
        }
    }
}

?>