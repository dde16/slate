<?php

namespace Slate\Neat\Attribute {
    use Attribute;
    use Slate\Metalang\MetalangAttribute;
    use Slate\Mvc\App;

#[Attribute(Attribute::TARGET_METHOD)]
    class Cache extends MetalangAttribute {
        public const NAME = "Cache";
    
        protected string $repo;
        protected ?int    $ttl = null;
        protected bool    $persistent;
    
        public function __construct(string $repo, int $ttl, bool $persistent = false) {
            if($ttl !== null ? $ttl < 1 : false)
                throw new \Error("The Time to Live must be a non-zero, positive, integer.");
    
            $this->ttl        = $ttl;
            $this->repo       = $repo;
            $this->persistent = $persistent;
        }
    
        public function getCacheKey(): string {
            return $this->parent->getDeclaringClass()->getName()."::".$this->parent->getName();
        }
    
        public function getRepo(): string {
            return $this->repo;
        }
    
        public function getTtl(): int|null {
            return $this->ttl;
        }
    
        public function consume($method): void {
            parent::consume($method);
    
            if(!$this->persistent)
                App::repo($this->repo)->forget($this->getCacheKey());
        }
    }
}

?>