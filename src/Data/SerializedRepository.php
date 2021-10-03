<?php

namespace Slate\Data {

    use Slate\Data\Repository\IRepositorySerialized;

    abstract class SerializedRepository extends Repository implements IRepositorySerialized {
        protected Serializer $serializer;

        public function __construct(string $serializer = "json", bool $autoforget = false) {
            parent::__construct($autoforget);
            $this->serializer = SerializerFactory::create($serializer);
        }

        public function serialize(mixed $value): string {
            return $this->serializer->serialize($value);
        }

        public function deserialize(string $value): mixed {
            return $this->serializer->deserialize($value);
        }

        public function getSerializer(): Serializer {
            return $this->serializer;
        }
    }
}

?>