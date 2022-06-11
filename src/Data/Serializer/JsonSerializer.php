<?php declare(strict_types = 1);

namespace Slate\Data\Serializer {

    use Closure;
    use Slate\Data\Serializer;

    class JsonSerializer extends Serializer {
        protected Closure $serializer;
        protected Closure $deserializer;

        public function __construct() {
            $this->serializer = Closure::fromCallable('json_encode');
            $this->deserializer = Closure::fromCallable('json_decode');
        }

        public function serialize(mixed $value, array $options = []): string {
            return ($this->{"serializer"})(...\Arr::merge($options, ["value" => $value]));
        }

        public function deserialize(string $value, array $options = []): mixed {
            return ($this->{"deserializer"})(...\Arr::merge($options, ["json" => $value, "associative" => true]));
        }
    }
}

?>