<?php

namespace Slate\Data {

    use Closure;

    class JitStructure {
        protected array  $stack = [];
        protected mixed  $current = null;
        protected int    $depth = 0;
        public array  $tree = [];

        public function get(string|array $path, mixed $fallback = null, bool &$fellback = null, array $currpath = []): mixed {
            if(is_string($path))
                $path = \Str::split($path, ".");


            if(\Arr::isEmpty($currpath)) {
                $fellback = true;

                $data = \Compound::get($this->tree, $path, null, $fellback);

                if(!$fellback)  
                    return $data;
            }
            
            $pathEmpty = \Arr::isEmpty($path);

            $tree = [];

            $items  = \Arr::filter($this->stack, fn($item) => !($item instanceof Closure));
            $groups = \Arr::filter($this->stack, fn($item) => ($item instanceof Closure));

            $this->stack = [];

            foreach($items as list($name, $data)) {
                $pathMatch = $name === $path[0];

                if($pathEmpty) {
                    $tree[$name] = $data;
                }
                else if($pathMatch) {
                    $tree[$name] = $data;
                }

                if(\Arr::isEmpty($currpath)) {
                    \Compound::set($this->tree, $path, $tree[$name], []);
                }

                if($pathMatch) {
                    return $tree[$name];
                }
            }

            foreach($groups as $group) {
                list($name, $closure) = $group;

                $pathMatch = $name === $path[0];

                if($pathEmpty || $pathMatch) {
                    $this->current = $group;
                    $closure();
                    $this->current = null;

                    $fellback = false;

                    if($pathEmpty) {
                        $tree[$name] = $this->get([], $fallback,  $fellback, [...$currpath, $name]);
                    }
                    else if($pathMatch) {
                        $tree[$name] = $this->get(\Arr::slice($path, 1), $fallback, $fellback, [...$currpath, $name]);
                    }

                    if(\Arr::isEmpty($currpath) && !$fellback) {
                        \Compound::set($this->tree, $path, $tree[$name], []);
                    }

                    if($pathMatch) {
                        return $tree[$name];
                    }
                }
            }

            if(count($path) === 1) {
                $fellback = true;
                return $fallback;
            }

            return $tree;
        }

        public function toArray(array $ancestors = []): array {
            
            $tree = [];

            list($items, $groups) = \Arr::cluster(
                $this->stack,
                fn($item) =>
                    (is_object($item)
                        ? (\Cls::implements($item, IJitStructureGroup::class) || $item instanceof Closure
                            ? 1
                            : 0
                        )
                        : 0
                    )
            );

            $this->stack = [];

            foreach($items as $data) {
                $key = null;

                if(is_object($data)) {
                    if(\Cls::implements($data, IJitStructureItem::class)) {
                        $data->consumeAncestors($ancestors);
                    }

                    if(\Cls::implements($data, IJitStructureKeyedNode::class)) {
                        $key = $data->getKey();
                    }
                }

                if($key === null) {
                    $tree[] = $data;
                }
                else {
                    $tree[$key] = $data;
                }
            }

            foreach($groups as $group) {
                $key = null;

                if(\Cls::implements($group, IJitStructureKeyedNode::class)) {
                    $key = $group->getKey();
                }
                
                $this->current = $group;
                $group();
                $this->current = null;

                $subtree = $this->toArray([...$ancestors, $group]);

                if($key === null) {
                    $tree[] = $subtree;
                }
                else if(\Arr::hasKey($tree, $key)) {
                    $tree[$key] = \Arr::merge(
                        $tree[$key],
                        $subtree
                    );
                }
                else {
                    $tree[$key] = $subtree;
                }
            }

            return $tree;
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