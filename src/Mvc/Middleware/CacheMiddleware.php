<?php

namespace Slate\Mvc\Middleware {

    use Slate\Http\HttpRequest;
    use Slate\Http\HttpResponse;
    use Slate\Mvc\App;
    use Slate\Mvc\Attribute\Postprocessor;
    use Slate\Mvc\Attribute\Preprocessor;
    use Slate\Mvc\Result\AnyResult;
    use Slate\Mvc\Result\DataResult;

    trait CacheMiddleware {
        /**
         * This will actually retrieve and send the cached data.
         *
         * @param HttpRequest $request
         * @param object $next
         * @return void
         */
        #[Preprocessor("Cache")]
        public function cachePreprocessor(HttpRequest $request, object $next) {
            
            if(($repo = $request->route->cache) !== null) {
                $key = $this->cacheKey($request);

                if(App::repo($repo)->has($key))
                    return (new AnyResult(App::repo($repo)->pull($key), bypass: true));
            }

            return $next($request);
        }

        /**
         * This is to be placed exactly after what you want to be cached, this is so it
         * doesn't capture, say, aesthetic postprocessors.
         *
         * @param HttpRequest $request
         * @param HttpResponse $response
         * @param mixed $data
         * @param object $next
         * @return mixed
         */
        #[Postprocessor("Cache")]
        public function cachePostprocessor(HttpRequest $request, HttpResponse $response, mixed $data, object $next): mixed {
            if(($repo = $request->route->cache) !== null) {
                $key = $this->cacheKey($request);
                $repo = App::repo($repo);

                if($repo->expired($key)) {
                    $repo->put($key, $data);
                }
            }

            return $next($request, $response, $data);
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
                        "query" => $request->query->toArray(),
                        "header" => \Arr::mapAssoc(
                            $request->headers->toArray(),
                            function($key, $value) {
                                return [\Str::lower($key), $value];
                            }
                        )
                    ]
                ], ".")
            );

            return $cacheKey;
        }
    }
}

?>