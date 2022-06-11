<?php declare(strict_types = 1);

namespace Slate\Structure {
    trait TProceduralArray {
        public array $refs;

        /**
         * Calls each group.
         *
         * @param callable $children
         *
         * @return mixed
         */
        protected function callProceduralArrayValueGenerator(callable $children): mixed {
            return $children($this);
        }
    
        /**
         * Defines the value that will replace a closure
         * or group when iterated over.
         *
         * @return mixed
         */
        protected function transformProceduralArrayCallableToCollection(callable $value): mixed {
            return [];
        }
    
        /**
         * Add a value to the current ref.
         *
         * @param mixed $value
         *
         * @return void
         */
        public function &pushProceduralArrayValue(mixed $value): mixed {
            $ref = &$this->refs[array_key_last($this->refs) ?? 0];
            $ref[] = $value;

            $key = array_key_last($ref);

            if($this->isProceduralArrayValueCallable($ref[$key])) {
                $ref[$key] = $this->transformProceduralArrayCallableToCollection($ref[$key]);

                $this->startProceduralNestedArray($ref[$key]);
                $this->referenceProceduralArrayValue($ref, $key);
                $this->callProceduralArrayValueGenerator($value);
                array_pop($this->refs);
                $this->endProceduralNestedArray();
            }

            $ref[$key] = $this->mapProceduralArrayValue($ref[$key], $ref);

            return $ref[$key];
        }
    
        /**
         * Called when pushing a new reference to the ref stack.
         * 
         * This is useful to override when working with children
         * that are stored eg. within an array of an object.
         *
         * @param array|object $ref
         * @param string|integer $key
         *
         * @return void
         */
        protected function referenceProceduralArrayValue(array|object &$ref, string|int $key): void {
            $this->refs[] = &$ref[$key];
        }
    
        /**
         * The function that checks whether a value is callable.
         *
         * @param mixed $group
         *
         * @return boolean
         */
        protected function isProceduralArrayValueCallable(mixed $group): bool {
            return is_callable($group);
        }

        /**
         * Map all values.
         *
         * @param mixed $value
         *
         * @return mixed
         */
        protected function mapProceduralArrayValue(mixed $value): mixed {
            return $value;
        }

        /**
         * Map all keys.
         *
         * @param mixed $value
         *
         * @return mixed
         */
        protected function mapProceduralArrayKey(mixed $value, string|int $key): mixed {
            return $key;
        }

        protected function startProceduralNestedArray(mixed &$ref): void {

        }

        protected function endProceduralNestedArray(): void {
            
        }
    }
}

?>