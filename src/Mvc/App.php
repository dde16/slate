<?php

namespace Slate\Mvc {

    use Slate\Foundation\App as FoundationApp;
    use Slate\Http\HttpRequest;
    use Slate\Http\HttpResponse;

    class App extends FoundationApp {
        protected HttpRequest $request;
        protected HttpResponse $response;

        public const PROVIDERS = [
            \Slate\Foundation\Provider\ConfigurationProvider::class,
            \Slate\Foundation\Provider\HandlerProvider::class,
            \Slate\Foundation\Provider\ConnectionProvider::class,
            \Slate\Foundation\Provider\QueueProvider::class,
            \Slate\Foundation\Provider\RepositoryProvider::class,
            \Slate\Mvc\Provider\RouteProvider::class
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
        
    }
}

?>