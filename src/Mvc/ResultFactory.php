<?php

namespace Slate\Mvc {
    use Slate\Utility\Factory;
    use Slate\Mvc\Result\JsonResult;
    use Slate\Mvc\Result\ScalarResult;

    class ResultFactory extends Factory {
        public const MAP = [
            \Type::OBJECT => JsonResult::class,
            \Type::ARRAY  => JsonResult::class,
            \Type::INT    => ScalarResult::class,
            \Type::STRING => ScalarResult::class,
            \Type::FLOAT  => ScalarResult::class,
            \Type::BOOL   => ScalarResult::class
        ];

        public static function instantiate(string $class, $differentiator, array $arguments): object {
            return new $class(...$arguments);
        }
    }
}

?>