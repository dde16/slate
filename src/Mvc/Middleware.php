<?php declare(strict_types = 1);

namespace Slate\Mvc {

    use Closure;
    use Slate\Http\HttpRequest;

    abstract class Middleware {
        public abstract function handle(HttpRequest $request, Closure $next): mixed;
    }
}

?>