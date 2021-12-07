<?php

namespace Slate\Utility {
    trait TWrap {
        // public const AROUND = NULL;

        public function __set(string $name, $value): void {
            $target = (property_exists($this->{static::AROUND}, $name) ? $this->{static::AROUND} : $this);
    
            $target->{$name} = $value;
        }
    
        public function __get(string $name): mixed {
            return $this->{$name} ?: $this->{static::AROUND}->{$name};
        }
    
        public function __call(string $name, array $arguments): mixed {
            return $this->{static::AROUND}->{$name}(...$arguments);
        }
    }
}

?>