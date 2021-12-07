<?php

namespace Slate\Neat {

    class InstanceCarry extends Carry {
        protected object $instance;

        public function __construct(object $primary, object $instance) {
            parent::__construct($primary);

            $this->instance = $instance;
        }
    
        public function __call(string $name, array $arguments): mixed {
            return \Cls::hasMethod($this->primary, $name)
                ? $this->primary->{$name}(...$arguments)
                : $this->instance->implementLeadingInstanceCarry($name, $arguments, $this);
        }
    }
}

?>