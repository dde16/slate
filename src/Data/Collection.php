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

        /**
         * These are functions that will return a result
         * rather than modifying the array in place.
         */
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
            "get"                    => [\Compound::class, "get"],
            "modify"                 => [\Arr::class, "modify"],
            "use"                    => [\Arr::class, "use"],
        ];

        /**
         * These functions will modify the array in place.
         */
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

        /**
         * Storing items.
         *
         * @var array
         */
        protected array $items;

        /**
         * Permissions given to the collection.
         *
         * @var integer
         */
        protected int   $permissions;

        /**
         * Flag determines whether the collection
         * can be written to or not.
         *
         * @var boolean
         */
        protected bool  $lock;

        /**
         * Whether on a method not being found,
         * try and call the method on each object
         * in the collection. 
         * 
         * Do note, however, that this wont raise any 
         * errors if a method isnt found. On the collection
         * but will on an individual object.
         * 
         * @var bool
         */
        protected bool $passthru;

        public function __construct(array $values = null, int $permissions = Collection::UNRESTRICTED) {
            $this->lock        = false;
            $this->permissions = $permissions;
            $this->items       = [];
            $this->passthru    = false;
            
            if($values !== NULL)
                $this->fromArray($values);
        }

        /**
         * Toggle passthru.
         *
         * @param boolean $passthru
         *
         * @return static
         */
        public function passthru(bool $passthru = true): static {
            $this->passthru = $passthru;

            return $this;
        }

        public function var(string $name): FieldPrepped {
            return (new FieldPrepped($name))->from($this->items);
        }
        
        public function object(string $name, string|bool $assert = false): object {
            $var = $this->var($name);

            if($assert === false) $var->fallback(null);
            else if($assert === true) $assert = null;

            return $this->var($name)->object($assert);
        }
        
        public function array(string $name, string|bool $assert = false): array {
            $var = $this->var($name);

            if($assert === false) $var->fallback(null);
            else if($assert === true) $assert = null;

            return $this->var($name)->array($assert);
        }
        
        public function bool(string $name, string|bool $assert = false): bool {
            $var = $this->var($name);

            if($assert === false) $var->fallback(null);
            else if($assert === true) $assert = null;

            return $this->var($name)->bool($assert);
        }
        
        public function int(string $name, string|bool $assert = false): int {
            $var = $this->var($name);

            if($assert === false) $var->fallback(null);
            else if($assert === true) $assert = null;

            return $this->var($name)->int($assert);
        }
        
        public function string(string $name, string|bool $assert = false): string {
            $var = $this->var($name);

            if($assert === false) $var->fallback(null);
            else if($assert === true) $assert = null;

            return $this->var($name)->string($assert);
        }
        
        public function float(string $name, string|bool $assert = false): float {
            $var = $this->var($name);

            if($assert === false) $var->fallback(null);
            else if($assert === true) $assert = null;

            return $this->var($name)->float($assert);
        }
        
        /**
         * Set an item within the collection.
         *
         * @param  mixed $offset
         * @param  mixed $value
         * @return void
         */
        public function set(string $offset, $value): void {
            \Compound::set($this->items, $offset, $value);
        }
        
        /**
         * Use the values of the collection to format a string.
         *
         * @param  mixed $format
         * @param  mixed $additionals
         * @return string
         */
        public function format(string $format, array $additionals = []): string {
            return \Str::format($format, \Arr::dotsByValue(array_merge($this->items, $additionals)));
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
            
            if(\Arr::hasKey(static::SPL_FUNCTIONS_RETURN, $method)) {
                $arguments = array_merge([&$this->items], $arguments);
                $method = static::SPL_FUNCTIONS_RETURN[$method];

                if(!\Fnc::exists($method)) {
                    throw new \Error(\Str::format(
                        "Collection middleware function {} doesnt exist.",
                        (is_array($method) ? \Arr::join($method, "::") : $method)
                    ));
                }

                $callback = (function() use($method, $arguments): mixed {
                    return \Fnc::call($method, $arguments);
                });
            }
            else if(\Arr::hasKey(static::SPL_FUNCTIONS_SET, $method)) {
                $arguments = array_merge([&$this->items], $arguments);
                $method = static::SPL_FUNCTIONS_SET[$method];

                $callback = (function() use($method, $arguments): void {
                    $items = \Fnc::call($method, $arguments);

                    if($items === NULL) {
                        throw new \BadFunctionCallException(
                            \Str::format(
                                "Standard PHP Library function {} for the Collection middleware requires returning a value.",
                                (is_array($method) ? \Arr::join($method, "::") : $method)
                            )
                        );
                    }

                    $this->fromArray($items);
                });
            }

            if($callback !== NULL)
                return $callback();

            if(!$this->passthru)
                throw new \Error(\Str::format(
                    "Call to undefined method {}::{}().",
                    static::class, $method
                ));

            foreach($this->items as $item)
                if(is_object($item) ? \Cls::hasMethod($item, $method) : $item)
                    $item->{$method}(...$arguments);

            return $this;
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