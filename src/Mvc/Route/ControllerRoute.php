<?php

namespace Slate\Mvc\Route {

    use Closure;
    use RuntimeException;
    use Slate\Http\HttpRequest;
    use Slate\Http\HttpResponse;
    use Slate\Http\HttpProtocol;
    use Slate\Exception\HttpException;
    use Slate\Facade\App;
    use Slate\Mvc\Attribute\Handler;
    use Slate\Mvc\Attribute\Middleware;
    use Slate\Mvc\Attribute\Route as RouteAttribute;
    use Slate\Mvc\Result;
    use Slate\Mvc\Route;
    use Throwable;

    class ControllerRoute extends Route {
        // public array  $targets;

        protected string $controller;
        protected string $action;
        protected ?RouteAttribute $attribute = null;

        public function __construct(string $pattern, array|string $target, bool $fallback = false) {
            parent::__construct($pattern, $fallback);
            
            if(is_string($target))
                $target = \Str::split($target, "@");
            
            if(count($target) < 2) {
                throw new \Error("A route's target must be [controller, action].");
            }

            $this->controller = $target[0];
            $this->action     = $target[1];

            $design = $this->controller::design();

            if(($this->attribute = $design->getAttrInstance(RouteAttribute::class, $this->action)) === null)
                throw new HttpException(500, "Controller action {$this->controller}::\${$this->action} doesn't exist.");

            if($this->attribute->methods)
                $this->method($this->attribute->methods);
        }

        public function go(HttpRequest $request, HttpResponse $response, array $match): mixed {
            $controllerClass = $match["controller"];
            $controllerAction = $match["action"];
            $request->route = $controllerRoute = $match["route"];

            $controllerInstance = new $controllerClass($match["webpath"]);

            $controllerResult = null;
            $controllerDesign = $controllerClass::design();

            $controllerMiddleware = $controllerClass::MIDDLEWARE;

            // try {
            $controllerHandlers       = $controllerClass::HANDLERS;

            $controllerClosures = array_merge(
                \Arr::column(\Arr::map(
                    $controllerMiddleware,
                    function($middleware) use($controllerDesign, $controllerInstance, $controllerClass, $request, $response) {
                        /** @var Middleware $middleware */
                        if(($_middleware = $controllerDesign->getAttrInstance(Middleware::class, $middleware)) === null)
                            throw new \Error("Unknown controller middleware {$controllerClass}->{$middleware}.");

                        $middleware = $_middleware;

                        return [
                            $middleware->getName(),
                            function() use($middleware, $controllerInstance, $request, $response) {
                                return $middleware->parent->getClosure($controllerInstance)($request, $response, ...func_get_args());
                            }
                        ];
                    }
                ), 0, 1),
                [
                    "Action" => function() use($request, $response, $controllerInstance, $controllerAction) {
                        return $controllerInstance->{$controllerAction}($request, $response);
                    }
                ]
            );

            return \Fnc::graph(
                $controllerClosures,
                function(): mixed {
                    throw new RuntimeException("This shouldn't happen.");
                },
                function(Throwable $throwable, Closure $next, Closure $jump) use($controllerHandlers, $controllerDesign, $controllerInstance, $controllerClass) {
                    return \Fnc::chain(
                        \Arr::map(
                            $controllerHandlers,
                            function($handlerName) use($controllerDesign, $controllerInstance, $controllerClass): Closure {
                                if(($handler = $controllerDesign->getAttrInstance(Handler::class, $handlerName)) === null)
                                    throw new \Error("Unknown handler '$handlerName' in controller '$controllerClass'.");
        
                                return $handler->parent->getClosure($controllerInstance);
                            }
                        ),
                        function(Throwable $throwable): void {
                            throw $throwable;
                        },
                        [$throwable, $jump]
                    );
                }
            );
        }

        public function match(HttpRequest $request, array $patterns = [], bool $bypass = false): array|null {
            if(($result = parent::match($request, $patterns, $bypass)) !== null) {
                return array_merge(
                    $result, [
                        "controller" => $this->controller,
                        "action"     => $this->action,
                        "route"      => $this->attribute
                    ]
                );
            }

            return null;
        }
    }
}

?>