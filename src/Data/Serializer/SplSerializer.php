<?php

namespace Slate\Data\Serializer {

    use Closure;
    use Slate\Data\Serializer;

    class SplSerializer extends Serializer {
        protected Closure $serializer;
        protected Closure $deserializer;

        public function __construct() {
            $this->serializer   = Closure::fromCallable('serialize');
            $this->deserializer = Closure::fromCallable('unserialize');
        }

        public function serialize(mixed $value, array $options = []): string {
            return ($this->{"serializer"})(...\Arr::merge($options, ["value" => $value]));
        }

        public function deserialize(string $value, array $options = []): mixed {
            return ($this->{"deserializer"})(...\Arr::merge($options, ["data" => $value]));
        }
    }
}

?>