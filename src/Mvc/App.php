<?php

namespace Slate\Mvc {

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

        public const PROVIDERS = [
            \Slate\Foundation\Provider\ConfigurationProvider::class,
            \Slate\Foundation\Provider\HandlerProvider::class,
            \Slate\Foundation\Provider\ConnectionProvider::class,
            \Slate\Foundation\Provider\ShmProvider::class,
            \Slate\Foundation\Provider\QueueProvider::class,
            \Slate\Foundation\Provider\RepositoryProvider::class
        ];

        public function __construct(string $root) {
            $this->request  = HttpRequest::capture();
            $this->response = new HttpResponse();

            parent::__construct($root);
        }

        /**
         * Get the request for the current lifecycle.
         *
         * @return HttpResponse|null
         */
        public function request(): HttpRequest|null {
            return $this->request;
        }

        /**
         * Get the response for the current lifecycle.
         *
         * @return HttpResponse|null
         */
        public function response(): HttpResponse|null {
            return $this->response;
        }

        public function run(): HttpResponse {
            ob_start();
            $request = $this->request();
            $response = $this->response();

            $response->elapsed("init");
            
            /** Start a buffer to avoid premature response sending */
            // ob_start();


            if(($match = Router::match($request)) !== null ? $match[1] !== null : false) {
                list($routeInstance, $routeMatch) = $match;

                $controllerArguments    = $routeMatch["arguments"];

                $routeMatch["route"] = $routeInstance;

                $request->parameters = $controllerArguments;

                $routeResult = $routeInstance->go($request, $response, $routeMatch);

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

                // ob_end_flush();
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