<?php

namespace Slate\Mvc\Attribute {
    use Attribute;
    use Slate\Metalang\MetalangAttribute;
    use Slate\Http\HttpMethod;
    use Slate\Http\HttpRequest;
    use UnexpectedValueException;

#[Attribute(Attribute::TARGET_METHOD)]
    class Route extends MetalangAttribute {
        public int     $methods;
        public ?string $cache = null;
        public ?string $cachekey = null;
        public bool    $requiresAuth;

        public function requiresAuth(): bool {
            return $this->requiresAuth;
        }

        public function __construct(array|string $methods = null, string|array $cache = null, bool $requiresAuth = false) {
            if($methods !== null) {
                if(\Any::isString($methods)) $methods = [$methods];
    
                $methods = \Arr::xor(
                    HttpMethod::tokenise(
                        \Arr::map(
                            $methods,
                            function($method) {
                                return \Str::upper($method);
                            }
                        )
                    )[0]
                );
    
                $this->methods = $methods;
            }
            else {
                $this->methods = HttpMethod::SUPPORTED;
            }

            $this->requiresAuth = $requiresAuth;

            if(is_string($cache))
                $this->cache = $cache;
            else if(is_array($cache))
                list($this->cache, $this->cachekey) = $cache;
        }


        public function getKeys(): string|array {
            return $this->parent->getName();
        }

        public function accepts(HttpRequest $request): bool {
            $method = $request->method;

            if(\Any::isString($method)) {
                if(($method = HttpMethod::getValue(\Str::uppercase($method))) == null) {
                    throw new UnexpectedValueException(\Str::format(
                        "'{}' is not a valid Http method.", $method
                    ));
                }
            }

            return \Integer::hasBits($this->methods, $method);
        }
    }
}

?>