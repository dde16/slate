<?php

namespace Slate\Data {

    use Closure;

    class JitStructure {
        protected array  $stack = [];
        protected mixed  $current = null;
        protected int    $depth = 0;

        //TODO: reverse
        public function toArray(): array {
            $root   = [];
            $refs   = [&$root];
            $ancestors = [];
            $pkey   = 0;

            while(!\Arr::isEmpty($this->stack)) {
                $current = array_pop($this->stack);
                $ref     = &$refs[array_key_last($refs)];

                if($current !== null) {
                    $key     = null;

                    if(\Cls::implements($current, IJitStructureKeyedNode::class)) {
                        $key = $current->getKey();
                    }

                    if(\Cls::implements($current, IJitStructureGroup::class) || $current instanceof Closure) {
                        $this->push(null);
                        $this->current = $current;
                        $current();
                        $this->current = null;

                        $ancestors[] = $current;

                        $ref[$pkey] = [];
                        $refs[] = &$ref[$pkey];

                        $pkey++;
                    }
                    else {
                        if(is_object($current) ? \Cls::implements($current, IJitStructureItem::class) : false) {
                            foreach(\Arr::reverse($ancestors) as $ancestor) {
                                $current = $ancestor->influence($current);
                            }
                        }

                        if($key === null) {
                            $ref[$pkey++] = $current;
                        }
                        else {
                            $ref[$key] = $current;
                        }
                    }
                }
                else {
                    $ref = \Arr::reverse($ref);
                    array_pop($refs);
                    array_pop($ancestors);
                }
            }

            
            return \Arr::reverse($root);
        }

        public function current(): ?array {
            return $this->current;
        }

        public function push(mixed $data): void {
            $this->stack[] = $data;
        }
    }
}

?>