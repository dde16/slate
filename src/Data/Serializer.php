<?php declare(strict_types = 1);

namespace Slate\Data {
    abstract class Serializer {
        abstract function serialize(mixed $value, array $options = []): string;
        abstract function deserialize(string $value, array $options = []): mixed;
    }
}

?>