<?php

 namespace Slate\Mvc {
    use Slate\Http\HttpRequest;
    
    use Slate\Mvc\Result\RedirectResult;

    use Closure;
    use Slate\Exception\HttpException;
    use Slate\Mvc\Result\ViewResult;
    
    class Router {
        const PATTERN_PART = "[A-Za-z0-9\-\.\_\~\!\$\&\'\(\)\*\+\,\;\=\:\@\%]+";
        const PATTERN_ACTION = "[a-zA-Z_][a-zA-Z0-9_]+";
        // const PATTERN_ACTION = "[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+";


        public static array   $routes   = [];
        public static array   $views    = [];
        public static ?Route $fallback = null;

        public static function many(string $pattern, array $targets): ControllerRoute {
            $route = new ControllerRoute($pattern, $targets);

            return static::$routes[$route->size][] = $route;
        }

        public static function view(string $pattern, string $view = null, array $data = []): ViewRoute {
            $route = new ViewRoute($pattern, $view ?: $pattern, $data);

            return static::$routes[$route->size][] = $route;
        }

        public static function redirect(string $pattern, string $redirect = null): void {
            static::add($pattern, function($request, $response) use($redirect) {
                return (new RedirectResult($redirect));
            });
        }

        public static function add(string|array $patterns, string|array|Closure $targets): void {
            if(is_string($patterns))
                $patterns = [$patterns];

            foreach($patterns as $pattern) {
                if($targets instanceof Closure) {
                    $route = new FunctionRoute($pattern, $targets);
                }
                else {
                    $route = new ControllerRoute($pattern, [$targets]);
                }

                static::$routes[$route->size][] = $route;
            }
        }

        public static function fallback(Closure|Route|array|string $fallback): void {

            if(is_array($fallback) || is_string($fallback)) {
                $fallback = new ControllerRoute("/", [$fallback]);
            }
            else if($fallback instanceof Closure) {
                $fallback = new FunctionRoute("/", $fallback);
            }

            static::$fallback = $fallback;
        }
        

        public static function match(HttpRequest $request): array|null {
            $size = \Str::count($request->path, "/");

            foreach((@static::$routes[$size] ?: []) as $index => $route){
                if(($match = $route->match($request)) !== NULL) {
                    return [$route, $match];
                }
            }

            if(static::$fallback !== null) {
                $imitation = clone $request;
                $imitation->path = "/";

                if(($match = static::$fallback->match($imitation)) === null)
                    throw new HttpException(500, "Fallback match is returning null.");

                return [static::$fallback, $match];
            }

            return  null;
        }
    }
}

?>