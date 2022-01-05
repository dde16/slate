<?php

namespace Slate\Mvc\Attribute {
    use Attribute;
    use ReflectionMethod;
    use Slate\Metalang\MetalangAttribute;
    use Slate\Http\HttpMethod;
    use Slate\Http\HttpRequest;
    use UnexpectedValueException;
    use Slate\Metalang\MetalangDesign;

    #[Attribute(Attribute::TARGET_METHOD)]
    class Route extends MetalangAttribute {
        public array|string|null $methods = null;
        public array|string|null $mimes = null;
        public ?string $cache             = null;
        public ?string $cachekey          = null;
        public bool    $requiresAuth;

        public ?float  $ttl = null;

        public function __construct(array|string $methods = null, array|string $mimes = null, string|array $cache = null, ?float $ttl = null, bool $requiresAuth = false) {
            $this->methods = $methods;
            $this->requiresAuth = $requiresAuth;
            $this->mimes = $mimes;

            if(is_string($cache))
                $this->cache = $cache;
            else if(is_array($cache))
                list($this->cache, $this->cachekey) = $cache;

            $this->ttl = $ttl;
        }

        public function requiresAuth(): bool {
            return $this->requiresAuth;
        }


        public function getKeys(): string|array {
            return $this->parent->getName();
        }
    }
}

?>