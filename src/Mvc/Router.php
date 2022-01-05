<?php

 namespace Slate\Mvc {
    use Slate\Http\HttpRequest;
    
    use Slate\Mvc\Result\RedirectResult;

    use Closure;
    use Slate\Data\Collection;
    use Slate\Data\JitStructure;
    use Slate\Mvc\Route\ControllerRoute;
    use Slate\Mvc\Route\FunctionRoute;
    use Slate\Mvc\Route\ViewRoute;
    use Slate\Utility\TMacroable;

class Router {
        use TMacroable;

        /**
         * JIT structure that allows the use of closures to define nested structures.
         *
         * @var JitStructure
         */
        public JitStructure $jit;

        /**
         * Stores global patterns for parameters.
         *
         * @var array
         */
        public array   $patterns = [];

        /**
         * Stores routes in the format
         * SCHEME => HOST => PORT => PATH
         *
         * @var array
         */
        public array   $routes   = [];

        /**
         * Flag whether the routes jit structure has been built into its routes format.
         * 
         * @var bool
         */
        public bool    $built    = false;

        /**
         * Map the routes on build.
         *
         * @var Closure|null
         */
        public ?Closure $map = null;

        public function __construct() {
            $this->jit = new JitStructure;
        }

        /**
         * Set the mapper for routes on build time.
         *
         * @param Closure $map
         *
         * @return void
         */
        public function map(Closure $map): void {
            $this->map = $map;
        }

        /**
         * Set a global pattern.
         *
         * @param string $name
         * @param string $pattern
         *
         * @return void
         */
        public function pattern(string $name, string $pattern): void {
            $this->patterns[$name] = $pattern;
        }

        /**
         * Set a path to resolve to multiple endpoints.
         *
         * @param string $pattern
         * @param array $targets
         *
         * @return Collection<Route>
         */
        public function many(string $pattern, array $targets): Collection {
            $routes = collect();

            foreach($targets as $name => $target) {
                $routes[] = $route = $this->add($pattern, $target);

                if(\Arr::isAssocOffset($name))
                    $route->named($name);
            }

            return $routes->passthru();
        }

        /**
         * Set a path to resolve to a view.
         *
         * @param string $pattern
         * @param string|null $view
         * @param array $data
         *
         * @return Route
         */
        public function view(string $pattern, string $view = null, array $data = []): Route {
            $route = new ViewRoute($pattern, $view ?: $pattern, $data);

            $this->jit->push($route);

            return $route;
        }

        /**
         * Group routes by multiple options.
         *
         * @param array $options
         * @param Closure $group
         *
         * @return RouteGroup
         */
        public function group(array $options, Closure $group):  RouteGroup {
            $this->jit->push($group = new RouteGroup($options, $group));
            return $group;
        }

        /**
         * Group routes to prepend a name to them.
         *
         * @param string $name
         * @param Closure $group
         *
         * @return RouteGroup
         */
        public function name(string $name, Closure $group): RouteGroup {
            $this->jit->push($group = new RouteGroup(["name" => $name], $group));
            return $group;
        }

        /**
         * Group routes to prepend a path segment to them.
         *
         * @param string $prefix
         * @param Closure $group
         *
         * @return RouteGroup
         */
        public function prefix(string $prefix, Closure $group): RouteGroup {
            $this->jit->push($group = new RouteGroup(["prefix" => $prefix], $group));
            return $group;
        }
 
        /**
         * Group routes into a common domain.
         *
         * @param string $domain
         * @param Closure $group
         *
         * @return RouteGroup
         */
        public function domain(string $domain, Closure $group): RouteGroup {
            $this->jit->push($group = new RouteGroup(["domain" => $domain], $group));
            return $group;
        }

        /**
         * Set a redirect route.
         *
         * @param string $pattern
         * @param string|null $redirect
         *
         * @return void
         */
        public function redirect(string $pattern, string $redirect = null): void {
            $this->add($pattern, fn() => (new RedirectResult($redirect)));
        }

        public function get(string|array $patterns, string|array|Closure $target): Route|Collection {
            return $this->add($patterns, $target)->method("get");
        }

        public function post(string|array $patterns, string|array|Closure $target): Route|Collection {
            return $this->add($patterns, $target)->method("post");
        }

        public function patch(string|array $patterns, string|array|Closure $target): Route|Collection {
            return $this->add($patterns, $target)->method("patch");
        }

        public function delete(string|array $patterns, string|array|Closure $target): Route|Collection {
            return $this->add($patterns, $target)->method("delete");
        }

        /**
         * Add one or more patterns to a single endpoint.
         *
         * @param string|array $patterns
         * @param string|array|Closure $target
         *
         * @return Route|Collection
         */
        public function add(string|array $patterns, string|array|Closure $target): Route|Collection {
            if(is_string($patterns))
                $patterns = [$patterns];

            $routes = collect()->passthru();

            foreach($patterns as $pattern) {
                $routes[] = $route = new ($target instanceof Closure ? FunctionRoute::class : ControllerRoute::class)($pattern, $target);

                $this->jit->push($route);
            }

            return (is_array($patterns) ? count($patterns) === 1 : true) ? $route : $routes;
        }

        /**
         * Get the current JIT structure.
         *
         * @return void
         */
        public function jit(): JitStructure {
            return $this->jit;
        }

        /**
         * Fallback for this current level.
         *
         * @param Closure|Route|array|string $fallback
         *
         * @return void
         */
        public function fallback(Closure|Route|array|string $fallback): void {
            if(is_array($fallback) || is_string($fallback)) {
                $fallback = new ControllerRoute("/", $fallback, true);
            }
            else if($fallback instanceof Closure) {
                $fallback = new FunctionRoute("/", $fallback, true);
            }

            $this->jit->push($fallback);
        }

        /**
         * Get the built Routes.
         *
         * @return array|null
         */
        public function routes(): ?array {
            if(!$this->built)
                $this->build;

            return $this->routes;
        }
        
        /**
         * Build routes from the jit structure.
         *
         * @return void
         */
        public function build(): void {
            $jit = $this->jit->toArray();

            foreach(\Arr::flatten($jit) as $route) {
                $scheme = $route->uri->scheme;
                $host   = $route->uri->host;
                $port   = $route->uri->port;

                if(!$route->isFallback()) {

                    if($this->map)
                        $route = ($this->map)($route);
                        
                    $this->routes[$scheme ?: "*"][$host ?: "*"][$port ?: "*"][] = $route;
                }
                else {
                    $path = [$scheme, $host, $port];

                    if(($furthest = \Arr::lastKey($path, fn($part) => $part !== null)) !== null) {
                        $path = \Arr::slice($path, 0, $furthest+1);
                    }

                    $path = \Arr::map($path, fn($part) => $part ?: "*");

                    if($this->map)
                        $route = ($this->map)($route);
                    
                    \Compound::set($this->routes, [...$path, "__fallback"], $route, []);
                }
            }

            $this->built = true;
        }

        /**
         * Try and match a request to a route.
         *
         * @param HttpRequest $request
         *
         * @return array|null
         */
        public function match(HttpRequest $request): array|null {
            if(!$this->built) 
                $this->build();

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
                                    foreach($this->routes[$scheme][$host][$port] as $route) {
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