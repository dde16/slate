<?php declare(strict_types = 1);

namespace Slate\IO {
    use Slate\Utility\Factory;

    class StreamFactory extends Factory {
        public const MAP = [
            "r"  => StreamReader::class,
            "r+" => Stream::class,
            "w"  => StreamWriter::class,
            "w+" => Stream::class,
            "a"  => StreamWriter::class,
            "a+" => StreamReader::class,
            "x"  => StreamWriter::class,
            "x+" => Stream::class,
            "c"  => StreamWriter::class,
            "c+" => Stream::class
        ];

    }
}

?>