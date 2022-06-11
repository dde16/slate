<?php declare(strict_types = 1);

namespace Slate\Mvc {

    use Error;
    use RuntimeException;
    use Slate\Http\HttpMethod;
    use Slate\Http\HttpRequest;
    use Slate\Utility\TMacroable;
    use Slate\Utility\TObjectHelpers;
    use UnexpectedValueException;

    abstract class Route {
        use TMacroable;
        use TObjectHelpers;

        public int      $methods;
        public array    $mimes;
        public ?string  $name;
        public int      $size;

        public array    $middleware;
        public array    $withoutMiddleware;

        protected bool  $fallback;

        public RouteUri $uri;
    
        public function __construct(string $pattern, bool $fallback = false) {
            $this->methods  = HttpMethod::SUPPORTED;
            $this->uri      = new RouteUri($pattern);
            $this->size     = $this->uri->slashes();
            $this->mimes    = [];

            $this->middleware = [];
            $this->withoutMiddleware = [];

            $this->name     = null;
            $this->fallback = $fallback;
        }

        public function https(): static {
            $this->uri->scheme = "https";
            return $this;
        }

        public function http(): static {
            $this->uri->scheme = "http";
            return $this;
        }

        public function methods(): int {
            return $this->methods;
        }

        public function mimes(): array {
            return $this->mimes;
        }

        public function mime(string ...$mimes): static {
            $this->mimes = [$this->mimes, ...$mimes];

            return $this;
        }

        public function acceptsMime(?string $mime = null): bool {
            return
                !\Arr::isEmpty($this->mimes, $mime)
                    ? ($mime !== null ? \Arr::contains($this->mimes, $mime) : false)
                    : true;
        }

        public function method(string|array $methods): static {
            $methods = \Arr::xor(
                HttpMethod::tokenise(
                    \Arr::map(
                        \Arr::always($methods),
                        fn($method) => \Str::upper($method)
                    )
                )[0]
            );
        
            $this->methods = $methods;

            return $this;
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

        public function acceptsProtocol(string $protocol): bool {
            return $this->uri->scheme !== null ? $protocol === $this->uri->scheme : true;
        }

        public function acceptsMethod(string|int $method): bool {
            if(is_string($method)) {
                if(($method = HttpMethod::getValue(\Str::uppercase($method))) == null) {
                    throw new UnexpectedValueException(\Str::format(
                        "'{}' is not a valid Http method.", $method
                    ));
                }
            }
            else if($method === 0 || $method > max(HttpMethod::getValues())) {
                throw new RuntimeException("Invalid http method.");
            }

            return \Integer::hasBits($this->methods, $method);
        }

        public function accepts(HttpRequest $request): bool {
            return
                $this->acceptsMethod($request->method)
                && $this->acceptsMime(!empty($mime = $request->headers["content-type"]) ? \Str::trim(\Str::beforeFirst($mime, ";")) : null)
                && $this->acceptsProtocol($request->uri->scheme);
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
            if(!$this->accepts($request) && !$this->fallback)
                return null;

            if(($controllerArguments = $this->uri->match($request->uri->getPath(), $patterns)) === null && !$this->fallback)
                return null;
    
            return [$this, [
                "webpath"   => $request->uri->getPath(),
                "arguments" => $controllerArguments ?? []
            ]];
        }
    }
}
?>