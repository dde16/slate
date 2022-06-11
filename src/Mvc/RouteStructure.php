<?php declare(strict_types = 1);

namespace Slate\Mvc {

    use Slate\Structure\NestedClosures;

    class RouteStructure extends NestedClosures {
        protected $router;
        protected array $ancestors;

        public function __construct(Router $router) {
            parent::__construct();
            $this->router = $router;
            $this->ancestors = [];
        }

        protected function callProceduralArrayValueGenerator(mixed $children): mixed {
            return $children($this->router);
        }

        protected function transformProceduralArrayCallableToCollection(callable $current): mixed {
            return $current;
        }

        protected function isProceduralArrayValueCallable(mixed $group): bool {

            return $group instanceof RouteGroup;
        }

        protected function startProceduralNestedArray(mixed &$group): void {
            $this->ancestors[] = $group;
        }

        protected function endProceduralNestedArray(): void {
            array_pop($this->ancestors);
        }

        protected function mapProceduralArrayValue(mixed $route): mixed {
            if($route instanceof Route) 
                foreach(\Arr::reverse($this->ancestors) as $ancestor)
                    $ancestor->mapRoute($route);

            return $route;
        }
        
        protected function referenceProceduralArrayValue(array|object &$ref, string|int $key): void {
            $this->refs[] = &$ref[$key]->children;
        }
    }
}

?>