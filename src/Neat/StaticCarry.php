<?php

namespace Slate\Neat {
    use Slate\Neat\Attribute\Carry as CarryAttribute;

    class StaticCarry extends Carry {
        protected string $class;

        public function __construct(object $primary, string $class) {
            parent::__construct($primary);

            if(!class_exists($class))
                throw new \InvalidArgumentException("Class '$class' doesnt exist.");

            $this->class = $class;
        }

        public function setClass(string $class): void {
            $this->class = $class;
        }
    
        public function __call(string $name, array $arguments): mixed {
            return \Cls::hasMethod($this->primary, $name)
                ? $this->primary->{$name}(...$arguments)
                : ($this->class)::implementLeadingStaticCarry($name, $arguments, $this);
        }
    }
}

?>