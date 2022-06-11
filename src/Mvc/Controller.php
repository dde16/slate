<?php declare(strict_types = 1);

namespace Slate\Mvc {
    // use Slate\Utility\Log;
    use Slate\Exception\HttpException;
    use Slate\Metalang\MetalangClass;
    use Slate\Mvc\Result\ViewResult;

    abstract class Controller extends MetalangClass {
        public const MIDDLEWARE     = [];
        public const HANDLERS       = [];

        public static function action(string $action): array {
            return [static::class, $action];
        }

    }

}

?>