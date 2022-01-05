<?php

namespace Slate\Data {
    class UniqueArray extends BasicArray {
        public function __construct(array $items = []) {
            $this->{static::CONTAINER} = \Arr::unique($items);
        }

        public function offsetPush(mixed $value): void {
            if(!\Arr::contains($this->{static::CONTAINER}, $value))
                $this->{static::CONTAINER}[] = $value;
        }

        public function offsetAssign(string|int $offset, mixed $value): void {
            $existingOffset = \Arr::find($this->{static::CONTAINER}, $value);

            if($existingOffset === false) {
                throw new \Error(
                    \Str::format("Value '{}' already exists at offset '{}'.",
                    \Str::val($value),
                    $existingOffset
                ));
            }

            parent::offsetAssign($offset, $value);
        }
    }
}

?>