<?php declare(strict_types = 1);

namespace Slate\Neat\Attribute {
    use Attribute;
    use ReflectionMethod;
    use Slate\Metalang\MetalangAttribute;
    use Slate\Facade\App;
    use Slate\Metalang\MetalangDesign;

    #[Attribute(Attribute::TARGET_METHOD)]
    class Cache extends MetalangAttribute {    
        protected string $repo;
        protected ?float    $ttl = null;
        protected bool    $persistent;
    
        public function __construct(string $repo, float $ttl, bool $persistent = false) {
            if($ttl !== null ? $ttl < 1 : false)
                throw new \Error("The Time to Live must be a non-zero, positive, integer.");
    
            $this->ttl        = $ttl;
            $this->repo       = $repo;
            $this->persistent = $persistent;

            if(!$this->persistent)
                App::repo($this->repo)->forget($this->getCacheKey());
        }
    
        public function getCacheKey(): string {
            return $this->parent->getDeclaringClass()->getName()."::".$this->parent->getName();
        }
    
        public function getRepo(): string {
            return $this->repo;
        }
    
        public function getTtl(): float|null {
            return $this->ttl;
        }
    }
}

?>