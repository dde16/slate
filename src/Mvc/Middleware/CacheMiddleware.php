<?php

namespace Slate\Mvc\Middleware {

    use Slate\Http\HttpRequest;
    use Slate\Http\HttpResponse;
    use Slate\Facade\App;
    use Slate\Mvc\Attribute\Middleware;
    use Slate\Mvc\Result;
    use Slate\Mvc\Result\AnyResult;
    use Slate\Mvc\Result\DataResult;

    trait CacheMiddleware {
        /**
         * 
         *
         * @param HttpRequest $request
         * @param object $next
         * @return void
         */
        #[Middleware("Cache")]
        public function cacheMiddleware(HttpRequest $request, HttpResponse $response, object $next) {
            if(($repo = $request->route->cache) !== null) {
                $key = $this->cacheKey($request);
                $repo = App::repo($repo);

                if($repo->has($key)) {
                    $data = $repo->pull($key);

                    if(\Cls::isSubclassInstanceOf($data, Result::class)) {
                        $data->bypass(true);

                        return $data;
                    }

                    return (new AnyResult($data, bypass: true));
                }
            }

            $data = $next();

            if(($repo = $request->route->cache) !== null) {
                $key = $this->cacheKey($request);
                $repo = App::repo($repo);

                if($repo->expired($key))
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