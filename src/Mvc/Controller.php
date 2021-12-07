<?php

namespace Slate\Mvc {
    // use Slate\Utility\Log;
    use Slate\Exception\HttpException;
    use Slate\Metalang\MetalangClass;
    use Slate\Mvc\Result\ViewResult;

abstract class Controller extends MetalangClass {
        public const MIDDLEWARE     = [];
        public const HANDLERS       = [];

        protected string $webPath;

        public function __construct(string $webPath) {
            $this->webPath = $webPath;
        }

        // protected function view(array $data = [], string $mime = null): ViewResult {
        //     return view($this->relativePath, $data, $mime);
        // }

        public static function action(string $action): array {
            return [static::class, $action];
        }
    }

}

?>