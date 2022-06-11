<?php declare(strict_types = 1);

namespace Slate\Mvc\Middleware {

    use Closure;
    use Slate\Http\HttpRequest;
    use Slate\Http\HttpResponse;
    use Slate\Facade\App;
    use Slate\Mvc\Middleware;
    use Slate\Mvc\Result;
    use Slate\Mvc\Result\AnyResult;
    use Slate\Mvc\Result\DataResult;

    class CacheMiddleware extends Middleware {
        public function handle(HttpRequest $request, Closure $next): mixed {
            $refresh = $request->var("refresh")->fallback(false)->bool();

            if(($repoName = $request->route->cacheRepo) !== null && !$refresh) {
                $key = $this->cacheKey($request);
                $repo = App::repo($repoName);

                if($repo->has($key)) {
                    $data = $repo->pull($key);

                    if(is_object($data) ? \Cls::isSubclassInstanceOf($data, Result::class) : false) {
                        $data->bypass(true);

                        return $data;
                    }

                    return (new AnyResult($data, bypass: true));
                }
            }

            $data = $next($request);

            if($repoName !== null) {
                $repo ??= App::repo($repoName);
                $key = $this->cacheKey($request);

                if($repo->expired($key) || $refresh)
                    $repo->put($key, $data, $request->route->ttl);
            }

            return $data;
        }

        protected function cacheKey(HttpRequest $request) {
            $cacheKeyFormat = $request->route->cachekey ?? "{request.path}";
            $cacheKey = \Str::format(
                $cacheKeyFormat,
                \Arr::dotsByValue([
                    "request" => [
                        "path" => $request->uri->getPath(),
                        "protocol" => $request->uri->getScheme(),
                        "version" => $request->version,
                        "parameters" => $request->parameters->toArray(),
                        "query" => $request->uri->query,
                        "header" => \Arr::mapAssoc(
                            $request->headers->toArray(),
                            fn($key, $value) => [\Str::lower($key), $value]
                        )
                    ]
                ], ".")
            );

            return $cacheKey;
        }
    }
}

?>