<?php

namespace Slate\Data {

    use Slate\Data\Serializer\JsonSerializer;
    use Slate\Data\Serializer\SplSerializer;
    use Slate\Utility\Factory;

    class SerializerFactory extends Factory {
        public const MAP = [
            "json" => JsonSerializer::class,
            "spl"  => SplSerializer::class
        ];
    }
}

?>