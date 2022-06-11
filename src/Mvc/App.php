<?php declare(strict_types = 1);

namespace Slate\Mvc {

    use Closure;
    use RuntimeException;
    use Slate\Exception\HttpException;
    use Slate\Facade\Router;
    use Slate\Foundation\App as FoundationApp;
    use Slate\Http\HttpRequest;
    use Slate\Http\HttpResponse;
    use Slate\Mvc\Result\AnyResult;
    use Slate\Foundation\Provider;
    use Slate\Mvc\Result;
    use Slate\Mvc\Result\CommandResult;
    use Slate\Mvc\Result\DataResult;

    class App extends FoundationApp {
        protected HttpRequest $request;
        protected HttpResponse $response;

        public const MIDDLEWARE_GROUPS  = [];

        public const MIDDLEWARE = [];

        public const PROVIDERS = [
            \Slate\Foundation\Provider\ConfigurationProvider::class,
            \Slate\Foundation\Provider\HandlerProvider::class,
            \Slate\Foundation\Provider\ConnectionProvider::class,
            \Slate\Foundation\Provider\ShmProvider::class,
            \Slate\Foundation\Provider\QueueProvider::class,
            \Slate\Foundation\Provider\RepositoryProvider::class
        ];

        /**
         * Instantiated middleware storage.
         *
         * @var Middleware[]
         */
        protected array $middleware;

        public function __construct(string $root) {
            $this->request  = HttpRequest::capture();
            $this->response = new HttpResponse();

            $this->middleware = 
                \Arr::map(
                    static::MIDDLEWARE,
                    function(string $middlewareClass): Middleware {
                        if(!class_exists($middlewareClass))
                            throw new RuntimeException("Middleware class '$middlewareClass' doesn't exist.");

                        return (new $middlewareClass);
                    }
                )
            ;

            parent::__construct($root);
        }

        /**
         * Get the request for the current lifecycle.
         *
         * @return HttpResponse|null
         */
        public function request(): HttpRequest {
            return $this->request;
        }

        /**
         * Get the response for the current lifecycle.
         *
         * @return HttpResponse|null
         */
        public function response(): HttpResponse {
            return $this->response;
        }

        public function run(): HttpResponse {
            /** Start a buffer to avoid premature response sending */
            ob_start();

            $request = $this->request();
            $response = $this->response();

            $response->elapsed("init");

            if(($match = Router::match($request)) !== null ? $match[1] !== null : false) {
                list($routeInstance, $routeMatch) = $match;

                $response->elapsed("matched");

                $controllerArguments    = $routeMatch["arguments"];

                $routeMatch["route"] = $routeInstance;

                $request->parameters = $controllerArguments;
                
                $request->route = $routeMatch["route"];

                $routeResult = \Fnc::chain(
                    [
                        ...\Arr::values(
                            \Arr::map($this->middleware, function(Middleware $middleware): Closure {
                                return \Closure::fromCallable([$middleware, "handle"]);
                            })
                        ),
                        function(HttpRequest $request) use($response, $routeInstance, $routeMatch): mixed {
                            return $routeInstance->go($request, $response, $routeMatch);
                        }
                    ],
                    [$request]
                );

                if($routeResult !== null) {
                    if(!(is_object($routeResult) ? \Cls::isSubclassInstanceof($routeResult, Result::class) : false))
                        $routeResult = new AnyResult($routeResult);
                
                    if(\Cls::isSubclassInstanceof($routeResult, CommandResult::class)) {
                        $routeResult->modify($response);
                    }
                    else if(\Cls::isSubclassInstanceof($routeResult, DataResult::class)) {
                        $response->getBody()->write($routeResult->toString());
                        $response->headers["Content-Type"] = $routeResult->getMime();
                    }
                }

                return $response;
            }
            else {
                throw new HttpException(404, "No route by that path was found.");
            }

            ob_end_flush();
        }
        
    }
}

?>