<?php

namespace Slate\Data {

    use Closure;
    use Slate\IO\File;

    use Slate\Utility\TConvertable;
    
    use Slate\Data\IArrayConvertable;
    
    // class Collection implements \ArrayAccess, \Iterator, \Countable, IArrayConvertable {
    class Collection extends BasicArray implements IArrayConvertable {
        public const READABLE     = 0;
        public const WRITEABLE    = (1<<0);
        public const APPENDABLE   = (1<<1);
        public const DELETABLE    = (1<<2);
        public const UNRESTRICTED =
            Collection::READABLE
            | Collection::WRITEABLE
            | Collection::APPENDABLE
            | Collection::DELETABLE;

        public const SPL_FUNCTIONS_RETURN = [
            "chunk"                  => "array_chunk",
            "column"                 => "array_column",
            "countValues"            => "array_count_values",
            "diffAssoc"              => "array_diff_assoc",
            "diffKey"                => "array_diff_key",
            "diffAssocCallback"      => "array_diff_uassoc",
            "diffKeyCallback"        => "array_diff_ukey",
            "diff"                   => "array_diff",
            "intersectAssoc"         => "array_intersect_assoc",
            "intersectKey"           => "array_intersect_key",
            "intersectAssocCallback" => "array_intersect_uasso",
            "intersectKeyCallback"   => "array_intersect_ukey",
            "intersect"              => "array_intersect",
            "firstKey"               => "array_key_first",
            "lastKey"                => "array_key_last",
            "pop"                    => "array_pop",
            "product"                => "array_product",
            "rand"                   => "array_rand",
            "sum"                    => "array_sum",
            "walk"                   => "array_walk",
            "sortKeys"               => "ksort",
            "except"                 => [\Arr::class, "except"],
            "only"                   => [\Arr::class, "only"],
            "median"                 => [\Arr::class, "median"],
            "tally"                  => [\Arr::class, "tally"],
            "duplicates"             => [\Arr::class, "duplicates"],
            "mean"                   => [\Arr::class, "mean"],
            "min"                    => [\Arr::class, "min"],
            "max"                    => [\Arr::class, "max"],
            "sum"                    => [\Arr::class, "sum"],
            "subtract"               => [\Arr::class, "subtract"],
            "entries"                => [\Arr::class, "entries"],
            "every"                  => [\Arr::class, "every"],
            "unique"                 => [\Arr::class, "unique"],
            "keys"                   => [\Arr::class, "keys"],
            "values"                 => [\Arr::class, "values"],
            "join"                   => [\Arr::class, "join"],
            "lead"                   => [\Arr::class, "lead"],
            "mid"                    => [\Arr::class, "mid"],
            "middle"                 => [\Arr::class, "middle"],
            "all"                    => [\Arr::class, "all"],
            "any"                    => [\Arr::class, "any"],
            "numberOf"               => [\Arr::class, "count"],
            "isEmpty"                => [\Arr::class, "isEmpty"],
            "startEntry"             => [\Arr::class, "startEntry"],
            "start"                  => [\Arr::class, "start"],
            "endEntry"               => [\Arr::class, "endEntry"],
            "end"                    => [\Arr::class, "end"],
            "firstEntry"             => [\Arr::class, "firstEntry"],
            "first"                  => [\Arr::class, "first"],
            "lastEntry"              => [\Arr::class, "lastEntry"],
            "lastKey"                => [\Arr::class, "lastKey"],
            "last"                   => [\Arr::class, "last"],
            "find"                   => [\Arr::class, "find"],
            "findAll"                => [\Arr::class, "findAll"],
            "contains"               => [\Arr::class, "contains"],
            "has"                    => [\Arr::class, "has"],
            "get"                    => [\Arr::class, "get"],
            "gets"                   => [\Arr::class, "gets"],
            "modify"                 => [\Arr::class, "modify"],
            "use"                    => [\Arr::class, "use"],
        ];

        public const SPL_FUNCTIONS_SET = [
            "combine"                => "array_combine",
            "fillKeys"               => "array_fill_keys",
            "fill"                   => "array_fill",
            "filter"                 => "array_filter",
            "flip"                   => "array_flip",
            "merge"                  => "array_merge",
            "reduce"                 => "array_reduce",
            "replace"                => "array_replace",
            "reverse"                => "array_reverse",
            "shift"                  => "array_shift",
            "slice"                  => "array_slice",
            "splice"                 => "array_splice",
            "shuffle"                => "shuffle",
            "filter"                 => [\Arr::class, "filter"],
            "reduce"                 => [\Arr::class, "reduce"],
            "reverse"                => [\Arr::class, "reverse"],
            "decategorise"           => [\Arr::class, "decategorise"],
            "untokenize"             => [\Arr::class, "untokenize"],
            "tokenize"               => [\Arr::class, "tokenize"],
            "move"                   => [\Arr::class, "move"],
            "sort"                   => [\Arr::class, "sort"],
            "merge"                  => [\Arr::class, "merge"],
            "splice"                 => [\Arr::class, "splice"],
            "rekey"                  => [\Arr::class, "key"],
            "slice"                  => [\Arr::class, "slice"],
            "dekey"                  => [\Arr::class, "values"],
            "mapAssoc"               => [\Arr::class, "mapAssoc"],
            "map"                    => [\Arr::class, "map"],
            "cluster"                => [\Arr::class, "cluster"],
            
        ];

        protected array $items;
        protected int   $permissions;
        protected bool  $lock;

        public function __construct(array $values = null, int $permissions = Collection::UNRESTRICTED) {
            $this->lock        = false;
            $this->permissions = $permissions;
            $this->items       = [];
            
            if($values !== NULL)
                $this->fromArray($values);
        }
        
        /**
         * Set an item within the collection.
         *
         * @param  mixed $offset
         * @param  mixed $value
         * @return void
         */
        public function set(string $offset, $value): void {
            $this->offsetSet($offset, $value);
        }
                
        /**
         * Use the values of the collection to format a string.
         *
         * @param  mixed $format
         * @param  mixed $additionals
         * @return string
         */
        public function format(string $format, array $additionals = []): string {
            return \Str::format($format, array_merge($this->items, $additionals));
        }
        
        /**
         * A caller middleware to allow the calling of procedural functions without
         * writing out every function call.
         *
         * @param  mixed $method
         * @param  mixed $arguments
         * @return mixed
         */
        public function __call(string $method, array $arguments): mixed {
            $container = &$this->items;
            $arguments = array_merge([&$this->items], $arguments);
            
            if(\Arr::hasKey(static::SPL_FUNCTIONS_RETURN, $method)) {
                $method = static::SPL_FUNCTIONS_RETURN[$method];

                if(!\Fnc::exists($method)) {
                    throw new \Error(\Str::format(
                        "Collection middleware function {} doesnt exist.",
                        is_array($method) ? \Arr::join($method, "::") : $method
                    ));
                }

                $callback = (function() use($method, $arguments): mixed {
                    return \Fnc::call($method, $arguments);
                });
            }
            else if(\Arr::hasKey(static::SPL_FUNCTIONS_SET, $method)) {
                $method = static::SPL_FUNCTIONS_SET[$method];

                $callback = (function() use($method, $arguments): void {
                    $items = \Fnc::call($method, $arguments);

                    if($items === NULL) {
                        throw new \BadFunctionCallException(
                            \Str::format(
                                "Standard PHP Library function {} for the Collection middleware requires returning a value.",
                                is_array($method) ? \Arr::join($method, "::") : $method
                            )
                        );
                    }

                    $this->fromArray($items);
                });
            }

            if($callback !== NULL)
                return $callback();

            throw new \Error(\Str::format(
                "Call to undefined method {}::{}().",
                static::class, $method
            ));
        }


        /** 
         * Get the items of a collection to an array.
         * 
         * @see IArrayForwardConvertable::toArray()
         * 
         * @return array
         */
        public function toArray(): array {
            return $this->items;
        }

        /**
         * Set the items of a collection.
         * 
         * @see IArrayBackwardConvertable::fromArray()
         * 
         * @param array $array
         * 
         * @return void
         */
        public function fromArray(array $array): void {
            $merge = false;

            if(!\Integer::hasBits($this->permissions, Collection::WRITEABLE)) {
                if(\Integer::hasBits($this->permissions, Collection::APPENDABLE)) {
                    $merge = true;
                }
                else if($this->lock) {
                    throw new \Error("This Collection has already been written to and is locked.");
                }
                else {
                    $this->lock = true;
                }
            }

            if($merge) {
                $this->items = \Arr::merge($array, $this->items);
            }
            else {
                $this->items = $array;
            }
        }

    }
}

?>