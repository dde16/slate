<?php declare(strict_types = 1);

namespace Slate\Mvc {

    use Closure;
    use Slate\Http\HttpRequest;

    class RouteGroup {
        public ?string $domain = null;
        public ?string $prefix = null;
        public ?string $name   = null;
    
        protected ?Closure $callback = null;

        public array $children = [];
    
        public function __construct(array $options, ?Closure $callback = null) {
            $this->domain = @$options["domain"];
            $this->prefix = @$options["prefix"];
            $this->name  = @$options["name"];

            $this->callback = $callback;
        }

        public function matchDomain(HttpRequest $request): bool {
            $domain = $request->uri->host;
            $matches = true;

            if($this->domain !== null) {
                if(\Str::wrappedwith($this->domain, "//")) {
                    $matches = preg_match($this->domain, $domain);
                }
                else {
                    $matches = \Str::match($domain, $this->domain);
                }
            }

            return $matches;
        }

        public function matchProtocol(HttpRequest $request): bool {
            return ($this->protocol !== null) ? ($this->protocol === \Str::lower($request->uri->scheme)) : true;
        }

        public function getChildren(): array {
            return $this->children;
        }

        public function mapRoute(Route $route): Route {
            if($this->domain)
                $route->uri->host = $this->domain;

            if($this->name && $route->name) 
                $route->name = $this->name . ($route->name ?? "");

            if($this->prefix) {
                $route->uri->setPath(\Path::normalise($this->prefix) . $route->uri->getPath());
                $route->size = $route->uri->slashes();
            }

            return $route;
        }

        public function getRoutes(): array {
            return \Arr::reduce(
                $this->children,
                function(array $routes, Route|RouteGroup $group): array|Route {
                    if(\Cls::isSubclassInstanceOf($group, RouteGroup::class))
                        foreach($group->getRoutes() as $route)
                            $routes[] = ($route);
                    else 
                        $routes[] = $group;
                    
                    return $routes;
                },
                []
            );
        }
        
        public function match(HttpRequest $request, array $patterns = []): ?array {
            $matches = true;
            $match   = null;
            $fallback = null;

            $matches = $matches && $this->matchDomain($request) && $this->matchProtocol($request);

            if($matches) {
                /** @var Route|RouteGroup $route */
                foreach($this->children as $route) {
                    if($route instanceof Route ? !$route->isFallback() : true) {
                        if(($match = $route->match($request, $patterns)) !== null) {
                            break;
                        }
                    }
                    else {
                        $fallback = $route;
                    }
                }
            }

            if($matches && $match === null && $fallback !== null) 
                $match = $fallback->match($request);

            return $match;
        }

        public function __invoke(): mixed {
            return ($this->callback)(...func_get_args());
        }
    }
}

?>