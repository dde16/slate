<?php

namespace Slate\Mvc {
    use Slate\Http\HttpRequest;
    use Slate\Http\HttpResponse;
    use Closure;

    class FunctionRoute extends Route {
        protected Closure|string|array $callback;

        public function __construct(string $pattern, Closure $callback) { 
            parent::__construct($pattern);

            $this->callback = $callback;
        }

        public function go(HttpRequest $request, HttpResponse $response): mixed {
            return ($this->callback)($request, $response);
        }
    }
}

?>