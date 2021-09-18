<?php

namespace Slate\Mvc {
    use Slate\Http\HttpRequest;
    use Slate\Http\HttpResponse;
    
    abstract class Route {
        const PARAMETER_PATTERN = "/{(?'key'[\w\d\-\_\.]+)}/";

        public ?string $name;
        public string $original;
        public string $pattern;
        public string $regex;
        public array  $keys;
        public int    $size;
    
        public function __construct(string $pattern) {
            $this->original = $pattern;
            list(
                $this->pattern,
                $this->regex,
                $this->keys,
                $this->size
            ) = $this->build($pattern);
            $this->name = null;
        }
    
        public function build(string $pattern): array {
            $pattern = \Str::addPrefix($pattern, "/");
            $keys    = [];
            $size    = \Str::count($pattern, "/");

    
            $regex = \Str::wrapc(preg_replace_callback(
                Route::PARAMETER_PATTERN,
                function($matches) use(&$pattern, &$keys) {
                    $name = $matches["key"];
    
                    if(\Arr::contains($keys, $name))
                        throw new \Error(\Str::format("Duplicate parameter name for route '{}'.", $pattern));
    
                    $keys[] = $name;
            
                    return "(?'$name'[^\/]+)";
                },
                \Str::replace($pattern, "/", "\/")
            ), "/^$/");
    
            return [$pattern, $regex, $keys, $size];
        }

        public function format(array $data = []): string {
            $pattern = $this->pattern;
            $query = [];

            foreach($data as $key => $value) {
                if(\Arr::contains($this->keys, $key)) {
                    $pattern = \Str::replace($pattern, "{".$key."}", \Str::val($value));
                }
                else {
                    $query[] = [$key, $value];
                }
            }

            return $pattern.(!\Arr::isEmpty($query) ? "?". \Arr::join(\Arr::map(
                $query,
                function($entry) {
                    list($key, $value) = $entry;

                    return "$key=".urlencode($value);
                }
            ), "&") : "");
        }

        public function named(string $name): static {
            $this->name = $name;

            return $this;
        }
    
        public function match(HttpRequest $request, bool $bypass = false): array|null {
            if(preg_match($this->regex, $request->path, $matches) || $bypass) {
                $controllerArguments = [];

                foreach($this->keys as $key) {
                    $controllerArguments[$key] = $matches[$key];
                }

                return [
                    "webpath"   => $request->path,
                    "arguments" => $controllerArguments
                ];
            }
    
            return null;
        }
    }
}
?>