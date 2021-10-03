<?php

namespace Slate\Mvc {

    use Slate\Data\IJitStructureItem;
    use Slate\Http\HttpRequest;
    use Slate\Http\HttpResponse;
    use SplStack;

abstract class Route implements IJitStructureItem {
        public ?string  $name;
        public int      $size;

        protected bool  $fallback;

        public RouteUri $uri;
    
        public function __construct(string $pattern, bool $fallback = false) {
            $this->uri = new RouteUri($pattern);
            $this->size = $this->uri->slashes();

            $this->name = null;
            $this->fallback = $fallback;
        }

        public function isFallback(): bool {
            return $this->fallback;
        }

        public function consumeAncestors(array $parents): void {
            $prefixes = new SplStack;

            foreach($parents as $parent) {
                if($parent->domain)
                    $this->uri->host = $parent->domain;

                if($parent->prefix) {
                    $prefixes->push($parent->prefix);
                }

                if($parent->name) {
                    if(!$this->name)
                        $this->name = "";

                    $this->name = $parent->name . $this->name;
                }
            }

            while(!$prefixes->isEmpty()) {
                $prefix = $prefixes->pop();

                $this->uri->setPath(
                    \Path::normalise($prefix) . $this->uri->getPath()
                );
                
                $this->size = $this->uri->slashes();
            }
        }

        public function format(array $data = []): string {
            return $this->uri->restformat($data);
        }

        public function named(string $name): static {
            $this->name = $name;

            return $this;
        }
    
        public function match(HttpRequest $request, array $patterns = [], bool $bypass = false): array|null {
            $controllerArguments = $this->uri->match($request->uri->getPath(), $patterns);
    
            return $controllerArguments !== null || $this->fallback ? [
                "webpath"   => $request->uri->getPath(),
                "arguments" => $controllerArguments ?: []
            ] : null;
        }
    }
}
?>