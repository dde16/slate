<?php

namespace Slate\Data\Serializer {

    use Closure;
    use Slate\Data\Serializer;

    class SplSerializer extends Serializer {

        public function serialize(mixed $value, array $options = []): string {
            return base64_encode(serialize($value));
        }

        public function deserialize(string $value, array $options = []): mixed {
            return unserialize(base64_decode($value));
        }
    }
}

?>