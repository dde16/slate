<?php declare(strict_types = 1);

namespace Slate\Metalang {
    use Closure;

    class MetalangDynamicObject {
        protected array $getters;
        protected array $setters;
        protected array $macros;
        protected ?object $passthru;

        public function __construct(object $passthru = null) {
            $this->getters  = [];
            $this->setters  = [];
            $this->macros  = [];
            $this->passthru = $passthru;
        }

        public function passthru(): ?object {
            return $this->passthru;
        }

        public function getter(string $name, Closure $closure): static {
            $this->getters[$name] = $closure;

            return $this;
        }

        public function setter(string $name, Closure $closure): static {
            $this->setters[$name] = $closure;

            return $this;
        }

        public function macro(string $name, Closure $closure): static {
            $this->macros[$name] = $closure;

            return $this;
        }

        public function __get(string $name): mixed {
            if(\Arr::hasKey($this->getters, $name))
                return $this->getters[$name]->call($this);

            return $this->passthru ? $this->passthru->{$name} : $this->{$name};
        }

        public function __set(string $name, mixed $value): void {
            if(!\Arr::hasKey($this->setters, $name)) {
                if($this->passthru)
                    $this->passthru->{$name} = $value;
                else
                    $this->{$name} = $value;
            }
            else {
                $this->setters[$name]->call($this, $value);
            }
        }

        public function __call(string $name, array $arguments): mixed {
            if(\Arr::hasKey($this->macros, $name))
                return $this->macros[$name]->call($this, ...$arguments);

            return $this->passthru ? $this->passthru->{$name}(...$arguments) : $this->{$name}(...$arguments);
        }
    }
}

?>