<?php

namespace Slate\Data {

    use Generator;
    use Slate\Data\Iterator\ArrayExtendedIterator;
    use Slate\Data\Iterator\BufferedIterator;
    use Slate\Data\Iterator\StringIterator;
    use Slate\Utility\Factory;

    class IteratorFactory extends Factory {
        public const MAP = [
            Generator::class => BufferedIterator::class,
            "generator"      => BufferedIterator::class,

            Iterator::class  => BufferedIterator::class,
            "iterator"       => BufferedIterator::class,

            "array"          => ArrayExtendedIterator::class,
            
            "string"         => StringIterator::class
        ];
    }
}

?>