<?php

namespace Slate\Mvc\Route {
    use Slate\Http\HttpRequest;
    use Slate\Http\HttpResponse;

    use Slate\Mvc\Result\ViewResult;
    use Slate\Mvc\Route;

class ViewRoute extends Route {
        protected string $view;
        protected array  $data;

        public function __construct(string $pattern, string $view, array $data = [], bool $fallback = false) {
            parent::__construct($pattern, $fallback);

            $this->view = $view;
            $this->data = $data;
        }

        public function go(HttpRequest $request, HttpResponse $response): ViewResult {
            return (new ViewResult($this->view, $this->data));
        }
    }
}

?>