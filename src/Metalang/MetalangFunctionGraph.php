<?php

namespace Slate\Metalang {
    use Closure;
    
    class MetalangFunctionGraph {
        public ?string $next;
        public Closure $fallback;
        public array   $closures;
        public array   $called;
    
        public function __construct(array $closures, Closure $fallback) {
            $this->next = \Arr::firstEntry($closures)[0];
            $this->closures = $closures;
            $this->fallback = $fallback;
            $this->called   = [];
        }
    
        public function onto(string $name, string ...$fallbacks): void {
            $names   = [$name, ...$fallbacks];

            if(\Arr::contains($this->called, $name))
                throw new \Error("Already called");
    
            $this->next = \Arr::firstEntry(
                $this->closures,
                function($closure, $name) use($names) {
                    return
                        !\Arr::contains($this->called, $name)
                        && \Arr::contains($names, $name);
                }
            )[0];
        }
    
        public function __invoke(): mixed {
            $arguments = func_get_args();
    
            $next = $this->next;
    
            $this->called[] = $next;
            $this->next = null;
    
            return ($this->closures[$next] ?: $this->fallback)(
                ...[...$arguments, $this]
            );
        }
    }
}

?>