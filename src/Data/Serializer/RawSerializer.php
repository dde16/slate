<?php declare(strict_types = 1);

namespace Slate\Data\Serializer {

    use BadMethodCallException;
    use Slate\Data\Serializer;

    class RawSerializer extends Serializer {
        public function serialize(mixed $value, array $options = []): string {
            if(!\is_scalar($value))
                throw new BadMethodCallException("Only scalar values can be serialized using this serializer.");

            return \Str::tryparse($value);
        }

        public function deserialize(string $value, array $options = []): mixed {
            return $value;
        }
    }
}

?>