<?php

namespace Slate\Mvc {
    use Slate\Foundation\Kernel;
    use Slate\Utility\TMiddleware;
    use Slate\Utility\TUninstantiable;

    use Slate\Http\HttpRequest;
    use Slate\Http\HttpResponse;

    use Slate\Mvc\Route;
    use Slate\Mvc\Router;

    use Slate\Mvc\Result;

    use Slate\Mvc\Result\JsonResult;
    use Slate\Mvc\Result\ScalarResult;
    use Slate\Mvc\Result\FileResult;
    use Slate\Mvc\Result\FilesResult;

    use Slate\Mvc\Attribute\Preprocessor as PreprocessorAttribute;
    use Slate\Mvc\Attribute\Postprocessor as PostprocessorAttribute;
    use Slate\Mvc\Attribute\Route as RouteAttribute;

    final class App {
        use TUninstantiable;
        use TMiddleware;

        protected static array $middleware = [
            "http.request"                     => HttpRequest::class,
            "http.request.file"                => HttpRequestFile::class,

            "http.response"                    => HttpResponse::class,
            "http.response.file"               => HttpResponseFile::class,

            // Routing
            "routing.router"                   => Router::class,
            "routing.route"                    => Route::class,

            // Results
            "result.compound"                  => Result::class,
            "result.scalar"                    => Result::class,
            "result.file"                      => Result::class,
            "result.files"                     => Result::class,
        ];
        
        public static array $using      = [
            "http.request"                     => HttpRequest::class,
            "http.request.file"                => HttpRequestFile::class,

            "http.response"                    => HttpResponse::class,
            "http.response.file"               => HttpResponseFile::class,

            "routing.router"                   => Router::class,
            "routing.route"                    => Route::class,
            
            "result.compound"                  => JsonResult::class,
            "result.scalar"                    => ScalarResult::class,
            "result.file"                      => FileResult::class,
            "result.files"                     => FilesResult::class,
        ];

        protected static Kernel $kernel;
        
        public static function kernel(Kernel $kernel): void {
            static::$kernel = $kernel;
        }

        /**
         * The magic method that will, if any methods are called statically, try and 
         * call the singleton instance. This can create loops if not used carefully.
         * 
         * @return mixed
         */
        protected static function __callStatic(string $name, array $arguments): mixed {
            return static::$kernel->{$name}(...$arguments);
        }
    }
}

?>