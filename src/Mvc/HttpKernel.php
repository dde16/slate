<?php

namespace Slate\Mvc {

    use Slate\Exception\HttpException;
    use Slate\Foundation\Kernel;
    use Slate\Foundation\Stager;
    use Slate\Http\HttpRequest;
    use Slate\Http\HttpResponse;

    use Slate\Mvc\Result\CommandResult;
    use Slate\Mvc\Result\DataResult;

    class HttpKernel extends ConsoleKernel {
        public const NONE      = Kernel::NONE;
        public const CONFIGURE = ConsoleKernel::CONFIGURE;
        public const VERIFY    = ConsoleKernel::VERIFY;
        public const HTTP      = (1<<6);
        public const HANDLER   = ConsoleKernel::HANDLER;
        public const ROUTE     = (1<<7);

        protected ?HttpRequest  $request;
        protected ?HttpResponse $response;
        
        public const STAGES = [
            self::CONFIGURE,
            self::VERIFY,
            self::HTTP,
            self::HANDLER,
            self::CONNECTIONS,
            self::REPOSITORIES,
            self::QUEUES,
            self::ROUTE
        ];

        public function __construct(string $root) {
            parent::__construct($root);

            $this->request  = null;
            $this->response = null;
        }

        public function getRequest(): HttpRequest|null {
            return $this->request;
        }

        public function getResponse(): HttpResponse|null {
            return $this->response;
        }

        /**
         * Register the Http Request and Response from the environment.
         * 
         * @return void
         */
        #[Stager(self::HTTP)]
        protected function register(): void {
            $requestClass  = (App::use("http.request"));
            $responseClass = (App::use("http.response"));

            $this->request  =     $requestClass::capture();
            $this->response = new $responseClass();
        }

        #[Stager(self::ROUTE)]
        protected function route(): void {
            $this->response->elapsed("init");
            
            /** Start a buffer to avoid premature response header sending. */
            ob_start();

            $path = $this->request->uri->getPath();

            $mvcIndexPath  = Env::get("mvc.index.path",  [ "important" => true ]);
            $mvcPublicPath = Env::get("mvc.public.path", [ "important" => true ]);

            $mvcViewsPath = Env::get("mvc.view.path",       [ "important" => true ]);


            if(($match = Router::match($this->request)) !== null ? $match[1] !== null : false) {
                list($routeInstance, $routeMatch) = $match;

                $controllerWebPath      = $routeMatch["webpath"];
                $controllerArguments    = $routeMatch["arguments"];

                $this->request->parameters = $controllerArguments;

                $routeResult = $routeInstance->go($this->request, $this->response, $routeMatch);

                if($routeResult !== null) {
                    if(!(is_object($routeResult) ? \Cls::isSubclassInstanceof($routeResult, Result::class) : false)) {
                        $routeResult = ResultFactory::create(
                            \Any::getType($routeResult, tokenise: true),
                            [$routeResult]
                        );
                    }
                
                    if(\Cls::isSubclassInstanceof($routeResult, CommandResult::class)) {
                        $routeResult->modify($this->response);
                    }
                    else if(\Cls::isSubclassInstanceof($routeResult, DataResult::class)) {
                        $this->response->getBody()->write($routeResult->toString());
                        $this->response->headers["Content-Type"] = $routeResult->getMime();
                    }
                }


                $this->response->send();

                ob_end_flush();
            }
            else {
                throw new HttpException(404, "No route by that path was found.");
            }
        }
    }
}

?>