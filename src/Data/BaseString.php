<?php

namespace Slate\Data {
    /**
     * A class that can convert to/from a substitution base alongside incrementing
     * and decrementing. This is good for codes greater than a 64 bit integer.
     * This class will be superseded by IntArray.
     */
    class BaseString {
        protected string $base;
        protected array  $components;

        public function __construct(string $base) {
            $this->base  = $base;
        }

        public function fromString(string $string): void {
            $this->components = \Arr::map(
                \Str::split($string),
                fn($char) => \Str::find($this->base, $char)
            );
        }

        public function increment(int $by = 1): void {
            $baseLength  = strlen($this->base)-1;
            $valuesLength = count($this->components)-1;

            $this->components[$valuesLength] += $by;

            for($valueIndex = $valuesLength; $valueIndex >= 0; $valueIndex--) {
                $value = &$this->components[$valueIndex];

                if($value > $baseLength) {
                    $value = 0;

                    if($valueIndex === $baseLength) {
                        $this->components = [$this->base[0], ...$this->components];
                    }
                    else {
                        $this->components[$valueIndex-1]++;
                    }
                }
            }
        }

        public function decrement(int $by): void {
            $baseLength  = strlen($this->base)-1;
            $valuesLength = count($this->components)-1;

            $this->components[$valuesLength] -= $by;

            for($valueIndex = $valuesLength; $valueIndex >= 0; $valueIndex--) {
                $value = &$this->components[$valueIndex];

                if($value < 0) {
                    $value = $baseLength;
                    $this->components[$valueIndex-1] -= 1;
                }
            }
        }

        public function toArray(): array {
            return $this->components;
        }

        public function toString(): string {
            return \Arr::join(
                \Arr::map($this->components, fn($v) => $this->base[$v])
            );
        }
    }
}

?>