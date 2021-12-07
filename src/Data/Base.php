<?php

namespace Slate\Data {
    /**
     * A class that can convert integers to/from strings 
     * using a base.
     * 
     * This class will be superseded by the IntArray.
     */
    class Base {
        protected array $base;

        public function __construct(array|string $base) {
            $this->base =
                is_string($base)
                    ? \Str::split($base)
                    : $base;
        }

        public function fromString(string $string): int {
            return \Integer::fromBase($string, $this->base);
        }

        public function toString(int $integer): string {
            return \Integer::toBase($integer, $this->base);
        }
    }
}

?>