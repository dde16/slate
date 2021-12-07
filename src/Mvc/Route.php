<?php

namespace Slate\Mvc {

    use Slate\Data\IJitStructureItem;
    use Slate\Http\HttpMethod;
    use Slate\Http\HttpRequest;
    use Slate\Http\HttpResponse;
    use Slate\Utility\TMacroable;
    use SplStack;
    use UnexpectedValueException;

abstract class Route implements IJitStructureItem {
        use TMacroable;

        public int      $methods;
        public ?string  $name;
        public int      $size;

        protected bool  $fallback;

        public RouteUri $uri;
    
        public function __construct(string $pattern, bool $fallback = false) {
            $this->methods = HttpMethod::SUPPORTED;
            $this->uri = new RouteUri($pattern);
            $this->size = $this->uri->slashes();

            $this->name = null;
            $this->fallback = $fallback;
        }

        public function get(): static {
            return $this->method("get");
        }

        public function post(): static {
            return $this->method("post");
        }

        public function patch(): static {
            return $this->method("patch");
        }

        public function delete(): static {
            return $this->method("delete");
        }

        public function method(string|array $methods): static {

            $methods = \Arr::xor(
                HttpMethod::tokenise(
                    \Arr::map(
                        \Arr::ensure($methods),
                        fn($method) => \Str::upper($method)
                    )
                )[0]
            );
        
            $this->methods = $methods;

            return $this;
        }

        public function accepts(HttpRequest $request): bool {
            $method = $request->method;
        
            if(is_string($method)) {
                if(($method = HttpMethod::getValue(\Str::uppercase($method))) == null) {
                    throw new UnexpectedValueException(\Str::format(
                        "'{}' is not a valid Http method.", $method
                    ));
                }
            }
        
            return \Integer::hasBits($this->methods, $method);
        }

        public function isFallback(): bool {
            return $this->fallback;
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
    
            return ($controllerArguments !== null && $this->accepts($request)) || $this->fallback ? [
                "webpath"   => $request->uri->getPath(),
                "arguments" => $controllerArguments ?: []
            ] : null;
        }
    }
}
?>