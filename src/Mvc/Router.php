<?php

 namespace Slate\Mvc {
    use Slate\Http\HttpRequest;
    
    use Slate\Mvc\Result\RedirectResult;

    use Closure;
    use Slate\Data\JitStructure;
    use Slate\Exception\HttpException;
    use Slate\Mvc\Result\ViewResult;
    use Slate\Mvc\Route\ControllerRoute;
    use Slate\Mvc\Route\FunctionRoute;
    use Slate\Mvc\Route\ViewRoute;
    use Slate\Utility\TSingleton;

    /** @method static void         pattern(string $name, string $pattern) */
    /** @method static Route        many(string $pattern, array $targets) */
    /** @method static Route        view(string $pattern, string $view = null, array $data = []) */
    /** @method static RouterGroup  group(array $options, Closure $group) */
    /** @method static RouterGroup  name(string $name, Closure $group) */
    /** @method static RouterGroup  prefix(string $prefix, Closure $group) */
    /** @method static RouterGroup  domain(string $domain, Closure $group) */
    /** @method static void         redirect(string $pattern, string $redirect = null) */
    /** @method static ?Route       add(string|array $patterns, string|array|Closure $targets) */
    /** @method static void         jit() */
    /** @method static void         fallback(Closure|Route|array|string $fallback) */
    /** @method static ?array       routes() */
    /** @method static void         build() */
    /** @method static array|null   match(HttpRequest $request) */
    class Router {
        use TSingleton;

        public JitStructure $jit;

        public array   $patterns = [];

        public array   $routes   = [];
        public array   $views    = [];
        public ?Route $fallback = null;

        public bool    $built    = false;

        protected function __construct() {
            $this->jit = new JitStructure;
        }

        protected function pattern(string $name, string $pattern): void {
            $this->patterns[$name] = $pattern;
        }

        protected function many(string $pattern, array $targets): Route {
            $this->jit->push($route = new ControllerRoute($pattern, $targets));

            return $route;
        }

        protected function view(string $pattern, string $view = null, array $data = []): Route {
            $route = new ViewRoute($pattern, $view ?: $pattern, $data);

            $this->jit->push($route);

            return $route;
        }

        protected function group(array $options, Closure $group):  RouterGroup {
            $this->jit->push($group = new RouterGroup($options, $group));
            return $group;
        }

        protected function name(string $name, Closure $group): RouterGroup {
            $this->jit->push($group = new RouterGroup(["name" => $name], $group));
            return $group;
        }

        protected function prefix(string $prefix, Closure $group): RouterGroup {
            $this->jit->push($group = new RouterGroup(["prefix" => $prefix], $group));
            return $group;
        }
 
        protected function domain(string $domain, Closure $group): RouterGroup {
            $this->jit->push($group = new RouterGroup(["domain" => $domain], $group));
            return $group;
        }

        protected function redirect(string $pattern, string $redirect = null): void {
            $this->add($pattern, function($request, $response) use($redirect) {
                return (new RedirectResult($redirect));
            });
        }

        protected function add(string|array $patterns, string|array|Closure $targets): ?Route {
            if(is_string($patterns))
                $patterns = [$patterns];

            foreach($patterns as $name => $pattern) {
                if($targets instanceof Closure) {
                    $route = new FunctionRoute($pattern, $targets);
                }
                else {
                    $route = new ControllerRoute($pattern, [$targets]);
                }

                if(\Arr::isAssocOffset($name))
                    $route->named($name);

                $this->jit->push($route);
            }

            if(count($patterns) === 1)
                return $route;

            return null;
        }

        protected function jit() {
            return $this->jit;
        }

        protected function fallback(Closure|Route|array|string $fallback): void {

            if(is_array($fallback) || is_string($fallback)) {
                $fallback = new ControllerRoute("/", [$fallback], true);
            }
            else if($fallback instanceof Closure) {
                $fallback = new FunctionRoute("/", $fallback, true);
            }

            $this->jit->push($fallback);
        }

        protected function routes(): ?array {
            if(!$this->built)
                $this->build;

            return $this->routes;
        }
        
        protected function build(): void {
            $jit = $this->jit->toArray();

            foreach(\Arr::flatten($jit) as $route) {
                $scheme = $route->uri->scheme;
                $host   = $route->uri->host;
                $port   = $route->uri->port;

                if(!$route->isFallback()) {
                    $this->routes[$scheme ?: "*"][$host ?: "*"][$port ?: "*"][$route->size][] = $route;
                }
                else {
                    $path = [$scheme, $host, $port];


                    if(($furthest = \Arr::lastKey($path, fn($part) => $part !== null)) !== null) {
                        $path = \Arr::slice($path, 0, $furthest+1);
                    }

                    $path = \Arr::map($path, fn($part) => $part ?: "*");
                    
                    \Compound::set($this->routes, [...$path, "__fallback"], $route, []);
                }
            }


            $this->built = true;
        }

        protected function match(HttpRequest $request): array|null {
            if(!$this->built) 
                $this->build();

            $requestSlashes = \Str::count($request->uri->getPath(), "/");

            $schemes = [$request->uri->getScheme(), "*"];
            $hosts   = [$request->uri->getHost(),   "*"];
            $ports   = ["*"];

            if($request->uri->getPort() !== null)
                $ports = [$request->uri->getPort(), ...$ports];

            foreach($schemes as $scheme) {
                if(\Arr::hasKey($this->routes, $scheme)) {
                    foreach($hosts as $host) {
                        if(\Arr::hasKey($this->routes[$scheme], $host)) {
                            foreach($ports as $port) {
                                if(\Arr::hasKey($this->routes[$scheme][$host], $port)) {
                                    foreach($this->routes[$scheme][$host][$port][$requestSlashes] as $route) {
                                        if(($match = $route->match($request, $this->patterns)) !== NULL) {
                                            return [$route, $match];
                                        }
                                    }

                                    if(\Arr::hasKey($this->routes[$scheme][$host][$port], "__fallback")) {
                                        $route = $this->routes[$scheme][$host][$port]["__fallback"];
        
                                        return [$route, $route->match($request, $this->patterns)];
                                    }
                                }
                            }

                            if(\Arr::hasKey($this->routes[$scheme][$host], "__fallback")) {
                                $route = $this->routes[$scheme][$host]["__fallback"];

                                return [$route, $route->match($request, $this->patterns)];
                            }
                        }
                    }

                    if(\Arr::hasKey($this->routes[$scheme], "__fallback")) {
                        $route = $this->routes[$scheme]["__fallback"];

                        return [$route, $route->match($request, $this->patterns)];
                    }
                }
            }

            return  null;
        }
    }
}

?>