<?php

namespace Slate\Mvc\Route {
    use Slate\Http\HttpRequest;
    use Slate\Http\HttpResponse;
    use Slate\Http\HttpProtocol;
    use Slate\Exception\HttpException;
    use Slate\Mvc\App;
    use Slate\Mvc\Attribute\Postprocessor;
    use Slate\Mvc\Attribute\Preprocessor;
    use Slate\Mvc\Attribute\Route as RouteAttribute;
    use Slate\Mvc\Result;
    use Slate\Mvc\Route;

    class ControllerRoute extends Route {
        public array  $targets;

        public function __construct(string $pattern, array $targets, bool $fallback = false) {
            parent::__construct($pattern, $fallback);

            $this->targets = \Arr::map(
                $targets,
                function($target) {    
                    if(\Any::isString($target)) {
                        $target = \Str::split($target, "@");
                    }
                    
                    if(count($target) < 2) {
                        throw new \Error("A route's target must be [classname, action].");
                    }

                    return $target;
                }
            );
        }

        public function go(HttpRequest $request, HttpResponse $response, array $match): mixed {
            $controllerClass = $match["controller"];
            $controllerAction = $match["action"];
            $request->route = $controllerRoute = $match["route"];

            $controllerInstance = new $controllerClass($match["webpath"]);

            $controllerResult = null;
            $controllerDesign = $controllerClass::design();

            // try {
            $controllerPreprocessors  = $controllerClass::PREPROCESSORS;
            $controllerPostprocessors = $controllerClass::POSTPROCESSORS;

            return \Fnc::chain(
                \Arr::map(
                    $controllerPreprocessors,
                    function($preprocessor) use($controllerDesign, $controllerInstance, $controllerClass) {
                        if(($preprocessor = $controllerDesign->getAttrInstance(Preprocessor::class, $preprocessor)) === null)
                            throw new \Error("Unknown preprocessor in controller '$controllerClass'.");

                        return $preprocessor->parent->getClosure($controllerInstance);
                    }
                ),
                [$request],
                function(HttpRequest $request) use($controllerDesign, $controllerClass, $controllerPostprocessors, $controllerRoute, $controllerInstance, $controllerAction, $response) {
                    try {
                        $controllerResult = $controllerInstance->{$controllerAction}($request, $response);
                    }
                    catch(\Throwable $throwable) {
                        $controllerResult = $throwable;
                    }

                    $bypass = false;

                    if(is_object($controllerResult) ? (is_subclass_of($controllerResult, Result::class) || $controllerResult instanceof Result) : false) {
                        $bypass = $controllerResult->bypasses();
                    }

                    if(!$bypass) {
                        $controllerResult = \Fnc::chain(
                            \Arr::map(
                                $controllerPostprocessors,
                                function($postprocessorName) use($controllerDesign, $controllerInstance, $controllerClass) {
                                    if(($postprocessor = $controllerDesign->getAttrInstance(Postprocessor::class, $postprocessorName)) === null)
                                        throw new \Error("Unknown postprocessor '$postprocessorName' in controller '$controllerClass'.");
            
                                    return $postprocessor->parent->getClosure($controllerInstance);
                                }
                            ),
                            [$request, $response, $controllerResult],
                            function(HttpRequest $request, HttpResponse $response, mixed $data) {
                                return $data;
                            }
                        );
                    }

                    return $controllerResult;
                }
            );
        }

        public function match(HttpRequest $request, array $patterns = [], bool $bypass = false): array|null {
            if(($result = parent::match($request, $patterns, $bypass)) !== null) {
                foreach($this->targets as list($controller, $action)) {
                    if(!class_exists($controller))
                        throw new HttpException(500, "Class '$controller' doesn't exist.");

                    $design = $controller::design();

                    if(($route = $design->getAttrInstance(RouteAttribute::class, $action, subclasses: true)) === null) {
                        throw new HttpException(500, "Controller action {$controller}::\${$action} doesn't exist.");
                    }

                    if($route->accepts($request)) {
                        return array_merge(
                            $result, [
                                "controller" => $controller,
                                "action"     => $action,
                                "route"      => $route
                            ]
                        );
                    }
                }
            }

            return null;
        }
    }
}

?>