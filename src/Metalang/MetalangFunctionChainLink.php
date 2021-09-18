<?php

namespace Slate\Metalang {
    use Closure;

    class MetalangFunctionChainLink {
        public                            $last;
        public                            $escape;
        //MetalangFunctionChainLink|Closure
        public ?object                    $next = null;

        public function __construct(array|Closure $last, $escape) {
            $this->last = $last;
            $this->escape = $escape;
        }

        public function __invoke() {
            $args = [
                ...\func_get_args()
            ];

            if($this->next === null && $this->escape !== null) {
                $args[] = null;
                $args[] = $this->escape;
            }
            else  {
                $args[] = $this->next ?: function(mixed $result): mixed {
                    return $result;
                };
                $args[] = null;
            }


            return \Fnc::call(
                $this->last,
                $args
            );
        }
    }
}

?>