<?php declare(strict_types = 1);

namespace Slate\Data\Serializer {

    use Closure;
    use Slate\Data\Serializer;

    /**
     * Serializer that uses the default serialize function, wrapped in a base64
     * encoding as null characters dont parse correctly once read from a file.
     */
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