<?php

namespace Slate\Mvc\Route {
    use Slate\Http\HttpRequest;
    use Slate\Http\HttpResponse;
    use Closure;
    use Slate\Mvc\Route;

class FunctionRoute extends Route {
        protected Closure|string|array $callback;

        public function __construct(string $pattern, Closure $callback, bool $fallback = false) { 
            parent::__construct($pattern, $fallback);

            $this->callback = $callback;
        }

        public function go(HttpRequest $request, HttpResponse $response): mixed {
            return ($this->callback)($request, $response);
        }
    }
}

?>