<?php

namespace Slate\Neat {

    use Closure;
    use RuntimeException;
    use Slate\Data\Iterator\ArrayRecursiveIterator;
    use Slate\Neat\Attribute\OneToAny;
    use Slate\Utility\TPassthru;

    class EntityQuery {
        use TPassthru;
    
        public const PASSTHRU = "root";
        public const PASSTHRU_METHODS = [
            "andWhere", "where", "orWhere",
            "orderBy", "orderByAsc", "orderByDesc",
            "limit", "offset",
            "get", "count", "scope",
            "toString"
        ];
        public const PASSTHRU_RETURN_THIS = [
            "andWhere", "where", "orWhere",
            "orderBy", "orderByAsc", "orderByDesc",
            "limit", "offset",
            "scope"
        ];
    
        /**
         * Stores the plan for the query.
         *
         * @var array
         */
        protected array $plan;
    
        /**
         * Provides the entry point for the query.
         *
         * @var EntityQueryVertex
         */
        public EntityQueryVertex $root;
    
        public function plan(array $plan) {
            $plan = \Arr::associate($plan, null, true);
    
            \Arr::mapRecursive(
                $plan,
                function(string|int $key, mixed $anon): array {
                    if(\Str::endswith($key, "?")) {
    
                        $flag = substr($key, -1);
            
                        if($anon instanceof Closure)
                            $anon = [
                                "@callback" => $anon
                            ];
            
                        if(!is_array($anon))
                            $anon = [];
            
                        $anon["@flag"] = $flag;
                        $key = substr($key, 0, -1);
                    }
            
                    return [$key, $anon];
                }
            );
    
            $this->plan = $plan;
    
            $iterator = new ArrayRecursiveIterator($plan);
            
            foreach($iterator as $path => $value) {
                $path = \Str::split($path, ".");
                $key = \Arr::last($path);
            
                $option = \Str::startswith($key, "@");


            
                if(($option || !\Any::isCompound($value) || $value instanceof Closure) && \Arr::all(\Arr::slice($path, 0, -1), fn($segment) => !\Str::startswith($segment, "@"))) {
                    $last = $this->root;
    
                    foreach($path as $index => $segment) {
                        $end = ($index === (count($path) - 1));
                        if(\Str::startswith($segment, "@") && $end) {
                            $last->option(\Str::removePrefix($segment, "@"), $value);
                        }
                        else {
                            $entity = $last->entity;
                            
                            /** @var EntityDesign $design */
                            $design = $entity::design();

                            /** @var OneToAny $along */
                            if(($along = $design->getAttrInstance(OneToAny::class, $segment)) === null)
                                throw new RuntimeException("Undefined relationship at path '" . \Arr::join(\Arr::slice($path, 0, $index+1), ".") . "'.");
                            
                            $foreignClass = $along->getForeignClass();

                            $next = new EntityQuerySubVertex($foreignClass);

                            if($end && $value instanceof Closure) {
                                $value($next);
                            }
            
                            $next->along($along);
            
                            if($value instanceof Closure)
                                $value($next);
            
                            $last->children[$segment]  = $next;
                            $last = $next;
                        }
                    }
                }
            }
    
        }
    
        public function __construct(string|Entity $target, array $plan = []) {
            $this->root = new EntityQueryRootVertex($target);
            $this->plan($plan);
        }
    
        public function get() {
            return $this->root->apply($this->root->fill(iterator_to_array($this->root->children())));
        }
    
        public function first(): ?Entity {
            $limit = $this->limit;
            $this->limit = 1;
    
            $models = $this->get();
    
            $this->limit = $limit;
    
            return \Arr::first($models);
        }
    
        public function _page(int $size, int $number): static {
            $query = clone $this;
    
            $query->limit(($size * ($number+1))+1, ($size * $number) + intval($number > 0));
    
            return $query;
        }
    
        public function page(int $size, int $number): array {
            $query = $this->_page($size, $number);
    
            $rows = $query->get();
    
            list($primaryChunk, $secondaryChunk) = \Arr::padRight(\Arr::chunk($rows, $size), [], 2);
    
            $hasNext = !\Arr::isEmpty($secondaryChunk);
    
            return [$primaryChunk, $hasNext, $query];
        }
    
        public function take(int $amount): array {
            $query = clone $this;
            $query->limit($amount);
    
            return $query->get();
        }
    }
}

?>