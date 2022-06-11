<?php declare(strict_types = 1);

namespace Slate\Structure {
    class NestedClosures extends ProceduralArray {
        /**
         * Add a value to the current ref.
         *
         * @param mixed $value
         *
         * @return void
         */
        public function push(mixed $value): void {
            $ref = &$this->refs[array_key_last($this->refs)];
            $ref[] = $value;
        }

        public function toArray(): array {
            $this->build();

            return $this->values;
        }

        public function build(): void {
            $ref = &$this->refs[array_key_last($this->refs)];
            array_pop($this->refs);

            foreach($ref as $key => $value) {
                $newKey = $this->mapProceduralArrayKey($value, $key);
    
                if($this->isProceduralArrayValueCallable($value)) {
                    $ref[$newKey] = $this->transformProceduralArrayCallableToCollection($value);
    
                    $this->startProceduralNestedArray($ref[$newKey]);
                    $this->referenceProceduralArrayValue($ref, $newKey);
                    $this->callProceduralArrayValueGenerator($value);
                    $this->build();
                    $this->endProceduralNestedArray();
                }
                
                $ref[$newKey] = $this->mapProceduralArrayValue($value, $ref);
    
                if($newKey !== $key)
                    unset($ref[$key]);
            }
        }
    }
}

?>