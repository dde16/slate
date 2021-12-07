<?php

namespace Slate\Metalang {

    use Closure;
    use Slate\Metalang\MetalangFunctionGraph;

    class MetalangHookGraph extends MetalangFunctionGraph {
        public string $class;
        public array $attributes;

        public function __construct(string $first, array $attributes, Closure $call, Closure $fallback) {
            parent::__construct(
                \Arr::map(
                    $attributes,
                    function($attribute) use($call) {
                        return (function() use($attribute, $call) {
                            return
                                $call($attribute, \func_get_args());
                        });
                    }
                ),
                $fallback
            );

            $this->next = $first;
            
            $this->attributes = \Arr::map(
                $attributes,    
                fn(Hook $attribute): array => [$attribute->getNextKeys(), 0]
            );
        }
        
        public function __invoke(): mixed {
            $arguments = \func_get_args();
    
            $next = $this->next;
            $this->next = null;

            if($next) {

                $attribute = &$this->attributes[$next];

                if($attribute[1] < count($attribute[0])) {
                    $this->next = $attribute[0][
                        $attribute[1]++
                    ];
                }
            }
    
            $this->called[] = $next;

            $arguments[] = &$this;

            list($match, $return) = (($next !== null ? @$this->closures[$next] : null) ?: $this->finally)(
                ...$arguments
            );

            return [$match, $return];
        }
    }
}

?>