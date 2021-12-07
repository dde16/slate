<?php

namespace Slate\Mvc {

    use Closure;
    use Slate\Data\IJitStructureGroup;
    use Slate\Neat\Attribute\Fillable;
    use Slate\Neat\Model;

    class RouteGroup implements IJitStructureGroup {
        public ?string $domain = null;
        public ?string $prefix = null;
        public ?string $name   = null;
    
        protected ?Closure $callback = null;
    
        public function __construct(array $options, ?Closure $callback = null) {
            $this->domain = @$options["domain"];
            $this->prefix = @$options["prefix"];
            $this->name  = @$options["name"];

            $this->callback = $callback;
        }

        public function influence(Route $route): Route {
            if($this->domain)
                $route->uri->host = $this->domain;

            if($this->name) {
                $route->name = $this->name . ($route->name ?? "");
            }

            if($this->prefix) {
                $route->uri->setPath(\Path::normalise($this->prefix) . $route->uri->getPath());
                $route->size = $route->uri->slashes();
            }

            return $route;
        }

        public function group(Closure $closure): static {
            $this->callback = $closure;

            return $this;
        }
    
        public function __invoke(): void {
            ($this->callback)();
        }
    }
}

?>