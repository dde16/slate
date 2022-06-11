<?php declare(strict_types = 1);

namespace Slate\Data {

    use Slate\Data\Serializer\JsonSerializer;
    use Slate\Data\Serializer\RawSerializer;
    use Slate\Data\Serializer\SplSerializer;
    use Slate\Utility\Factory;

    class SerializerFactory extends Factory {
        public const MAP = [
            "json" => JsonSerializer::class,
            "spl"  => SplSerializer::class,
            "raw"  => RawSerializer::class
        ];
    }
}

?>