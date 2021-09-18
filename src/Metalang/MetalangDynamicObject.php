<?php

namespace Slate\Metalang {

    use Closure;

    class MetalangDynamicObject {
        protected array $getters;
        protected array $setters;
        protected array $callers;

        public function __construct() {
            $this->getters = [];
            $this->setters = [];
            $this->callers = [];
        }

        public function getter(string $name, Closure $closure): static {
            $this->getters[$name] = $closure;

            return $this;
        }

        public function setter(string $name, Closure $closure): static {
            $this->setters[$name] = $closure;

            return $this;
        }

        public function caller(string $name, Closure $closure): static {
            $this->callers[$name] = $closure;

            return $this;
        }

        public function __get(string $name): mixed {
            if(\Arr::hasKey($this->getters, $name))
                return $this->getters[$name]();

            return $this->{$name};
        }

        public function __set(string $name, mixed $value): void {
            if(\Arr::hasKey($this->setters, $name))
                $this->setters[$name]($value);

            $this->{$name} = $value;
        }

        public function __call(string $name, array $arguments): mixed {
            if(\Arr::hasKey($this->callers, $name))
                $this->callers[$name](...$arguments);

            return $this->{$name}(...$arguments);
        }
    }
}

?>