<?php

abstract class Arr extends CompoundType {
    public const NAMES            = ["array"];
    public const VALIDATOR        = "is_array";
    public const CONVERT_FORWARD  = [ \Slate\Data\IArrayForwardConvertable::class, "toArray" ];
    public const CONVERT_BACKWARD = [ \Slate\Data\IArrayBackwardConvertable::class, "fromArray" ];

    public const DOTS_EVAL_ASSOC = 1;
    public const DOTS_EVAL_ARRAY = 2; // Evaluate arrays with integer keys
    public const DOTS_EVAL_ALL   = \Arr::DOTS_EVAL_ASSOC | \Arr::DOTS_EVAL_ARRAY;

    public const DOTS_BY_VALUE     = 4;
    public const DOTS_BY_REFERENCE = 8;

    public const MAP_VALUE = 0;
    public const MAP_ENTRY = 1;
    public const MAP_BOTH = \Arr::MAP_VALUE | \Arr::MAP_ENTRY;

    public const TOKENIZE_NORMAL  = 0;
    public const TOKENIZE_NULLIFY = 1;

    public const FILTER_VALUE = 0;
    public const FILTER_KEY   = ARRAY_FILTER_USE_KEY;
    public const FILTER_BOTH  = ARRAY_FILTER_USE_BOTH;

    public const POS_START  = (1<<0);
    public const POS_MIDDLE = (1<<1);
    public const POS_END    = (1<<2);

    public const RECURSIVE_BOTTOM_UP = (1<<0);
    public const RECURSIVE_TOP_DOWN = (1<<1);

    /**
     * Format/transform a compound value.
     *
     * @return void
     */
    public static function format(array $compound, array $format): array|object {
        return \Arr::mapAssoc(
            \Arr::associate($format, null, function(mixed $collision): Closure|string|null {
                return (is_array($collision) || is_string($collision) || ($collision instanceof Closure)) ? $collision : null;
            }),
            function($fromKey, $toKey) use($compound) {
                if($toKey === null)
                    $toKey = $fromKey;
                
                if(\Str::startswith($fromKey, "@")) {
                    $fromKey = \Str::removePrefix($fromKey, "@");

                    $toValue = $compound[$toKey];

                    return [
                        $compound[$fromKey],
                        $toKey instanceof Closure ? $toKey($toValue) : $toValue
                    ];
                }
                else {
                    $value =  $compound[$fromKey];

                    if($toKey instanceof Closure) {
                        $value = $toKey($value);
                        $toKey = $fromKey;
                    }
                    else if(is_array($toKey)) {
                        if(is_array($value)) 
                            $value = \Arr::format($value, $toKey);
                        
                        $toKey = $fromKey;
                    }


                    return [$toKey, $value];
                }
            }
        );
    }

    /**
     * Convert from an aggregated list to an associative array.
     * For example:
     * "key.0"   => "x"
     * "value.0" => "y"
     * 
     * Equates to
     * ["x" => "y"]
     * 
     * @param string $keyPrefix
     * @param string $valuePrefix
     * 
     * @return array
     */
    public static function fromList (array|ArrayAccess $array, string $keyPrefix, string $valuePrefix): array {
        return \Arr::mapAssoc(
            \Arr::filter(
                $array,
                function($key) use($keyPrefix) {
                    return \Str::startswith($key, $keyPrefix);
                },
                \Arr::FILTER_KEY
            ),
            function($key) use($array, $keyPrefix, $valuePrefix) {
                return [$array[$key], $array[
                    $valuePrefix.\Str::afterFirst($key, $keyPrefix)
                ]];
            }
        );
    }

    /**
     * Create a generator that provides information about positioning (start, middle and end).
     * 
     * @param array $array
     */
    public static function describe (array|ArrayAccess $array): \Generator {
        $length = count($array);

        foreach($array as $index => $value){
            $pos = 0;

            if($index === 0)
                $pos |= \Arr::POS_START;

            if($index === $length-1)
                $pos |= \Arr::POS_END;

            yield [
                $pos ?: \Arr::POS_MIDDLE,
                $value
            ];
        }
    }

    /**
     * Convert an array to a string list.
     * 
     * @param array  $array
     * @param string $delimiter What separates the elements.
     * @param string $wrapper   What to wrap each element in.
     * @param string $container What to wrap the whole list in.
     * 
     * @return string
     */
    public static function list (array|ArrayAccess $array, string $delimiter, string|array $itemWrap = "", string $listWrap = ""): string {
        return \Str::wrapc(
            \Arr::join(
                \Arr::map(
                    \Arr::filter($array),
                    function($value) use($itemWrap) {
                        $value = strval($value);

                        return is_string($itemWrap)
                            ? \Str::wrapc($value, $itemWrap)
                            : \Arr::join([
                                $itemWrap[0], $value, $itemWrap[1]
                            ]);
                    }
                ),
                $delimiter
            ),
            $listWrap
        );
    }

    /**
     * Will get the nested branches of an associative array, not unlike branches of a tree.
     * 
     * @param array $array
     * @param int   $flags Controls what to evaluate, takes same flags as dots.
     * 
     * @return array
     */
    public static function branches (array|ArrayAccess $array, int $flags = \Arr::DOTS_EVAL_ASSOC, string|array $path = []): array {
        $branches = [];

        foreach($array as $key => $value) {
            $tmpPath = [...$path, $key];

            if(\Any::isArray($value)) {
                $empty = \Arr::isEmpty($value);


                if(\Integer::hasBits($flags, \Arr::DOTS_EVAL_ARRAY) && !$empty) {
                    $branches = \Arr::merge(
                        $branches,
                        \Arr::branches(
                            $value,
                            $flags,
                            $tmpPath
                        )
                    );
                }
                else if(\Arr::isAssoc($value) && !$empty) {
                    $branches = \Arr::merge($branches, \Arr::branches($value, $flags, $tmpPath));
                }
                else {
                    $branches[] = [$tmpPath, $value];
                }
            }
            else {
                $branches[] = [$tmpPath, $value];
            }
        }

        return $branches;
    }

    /**
     * Get all values that are above a certain threshold.
     * 
     * @param array $array
     * @param int|float $threshold
     * 
     * @return array
     */
    public static function threshold (array|ArrayAccess $array, int|float $threshold): array {
        return \Arr::filter(
            $array,
            function($value) use($threshold){ 
                return $value >= $threshold;
            }
        );
    }

    /**
     * Get the total depth of an array.
     * 
     * @param array $array
     * @param int  $depth Parameter passing depth, do not set this when calling the function.
     * 
     * @return int
     */
    public static function depthOf (array|ArrayAccess $array, int $depth = 0): int {        
        $depth++;
        $maxDepth = $depth;
        
        foreach($array as $subkey => $subvalue) {
            if(is_array($subvalue)) {
                if(($nextDepth = \Arr::depthOf($subvalue, $depth)) !== null) {
                    if($nextDepth > $maxDepth) $maxDepth = $nextDepth;
                }
            }
        }
    
        return $maxDepth;
    }

    /**
     * Checks whether a value can be a valid offset (so an integer or string).
     * 
     * @param mixed $offset
     * @return bool
     */
    public static function isValidOffset(mixed $offset):bool {
        return (is_int($offset) || is_string($offset));
    }

    /**
     * Checks whether a value can be an associate offset (a string).
     * 
     * @param mixed $offset
     * @return bool
     */
    public static function isAssocOffset(mixed $offset): bool {
        return is_string($offset) && filter_var($offset, FILTER_VALIDATE_INT) === false;
    }

    /**
     * Checks if a given array starts with another.
     * 
     * @param array $array
     * @param array $start
     * @return bool
     */
    public static function startswith (array|ArrayAccess $array, array $start): bool {
        $slice = \Arr::slice($array, 0, count($start));

        return $slice == $start;
    }

    /**
     * Alias of array_intersect_assoc
     */
    public static function intersectAssoc (array|ArrayAccess $array, array ...$arrays): array {
        return array_intersect_assoc($array, ...$arrays);
    }

    /**
     * Checks whether all of the array's elements matches a given condition callback.
     * 
     * @param array    $array
     * @param callable $callback
     * @return bool
     */
    public static function all($array, callable $callback = null): bool {
        if ($callback === NULL) {
            $callback = fn($value) => $value == true;
        }

        foreach($array as $key => $value) {
            if($callback($value, $key) === false)
                return false;
        }

        return true;
    }

    /**
     * Checks whether any of the array's elements matches a given condition callback.
     * 
     * @param array    $array
     * @param callable $callback
     * @return bool
     */
    public static function any($array, callable $callback = null): bool {
        if ($callback === NULL)
            $callback = function ($value) { return($value == true); };

        $any = false;

        foreach($array as $key => $value) {
            if($any = $callback($value, $key)) break;
        }

        return $any;
    }

    /**
     * Checks whether a given value is array accessible, so is; an array itself or a class that has the ArrayAccess interface.
     * 
     * @param mixed $any
     * @return bool
     */
    public static function isAccessible(mixed $any): bool {
        if(is_object($any)) { $any = get_class($any); }
        if(is_array($any))  { return true; }
        
        if(is_string($any)) {
            if(class_exists($any)) {
                return \Cls::hasInterface($any, ArrayAccess::class);
            }
        }
        // else {
        //     throw new \InvalidArgumentException(
        //         "Value passed to is_array_accessible is not a class name or an object."
        //     );
        // }
        
        return false;
    }

    /**
     * Tries to determine whether an array is associative. 
     * Note: only checks for non-numeric keys, will not detect non-consecutive integer keys.
     * 
     * @param array $array
     * 
     * @return bool
     */
    public static function isAssoc (array|ArrayAccess $array, bool $sequential = false): bool{
        if($sequential === false)
            return \Arr::any(\Arr::keys($array), fn($key) => \Arr::isAssocOffset($key));

        $arrayKeys = \Arr::keys($array);
        $arraySize = count($arrayKeys);
        
        $notAssoc = true;
        
        $lastKey   = $arrayKeys[0];
        $nextIndex = 1;
        $nextKey   = $arrayKeys[$nextIndex];
        
        while($nextIndex < $arraySize && ($notAssoc = is_int($nextKey) ? ($lastKey === $nextKey-1) : false)) {
            $nextKey = $arrayKeys[$nextIndex++];
        } 
        
        return !$notAssoc;
    }

    /**
     * Count the values in an array.
     * 
     * @see count
     * 
     * @param array           $array
     * @param string|callable $filter
     * @param int             $mode
     * 
     * @return int
     */
    public static function count($array, string|callable $filter = null, int $mode = COUNT_NORMAL): int {
        if(\Any::isString($filter))
            $filter = fn($value) => \Any::traverse($value, $filter);

        if($filter !== null) {
            $count = 0;
            
            foreach($array as $key => $value) if($filter($value, $key)) $count++;

            return $count;
        }
        
        return count($array, $mode);
    }

    /**
     * Checks whether an array is empty.
     * 
     * @param array $array
     * 
     * @return bool
     */
    public static function isEmpty (array|ArrayAccess $array): bool {
        return count($array) === 0;
    }

    /**
     * Converts an array to a dot array (dot separated, flat array) by value.
     * 
     * @param array $array
     * @param int   $flags Flags to determine what compound values to evaluate
     * @param array $path  The path used for parameter passing to create the key in the associative array.
     * 
     * @return array
     */
    public static function dotsByValue (array|ArrayAccess $array,  string $using = ".",  int $flags = \Arr::DOTS_EVAL_ALL, string|array $path = []): array {
        if(\Any::isString($path)) $path = \Str::split($path, $using);

        $dots = [];

        foreach($array as $key => $value) {
            $curpath = [...$path, $key];
            $strpath = \Arr::join($curpath, $using);

            if(\Any::isArray($value)) {
                if($flags & \Arr::DOTS_EVAL_ARRAY) {
                    $dots = \Arr::merge(
                        $dots,
                        \Arr::dotsByValue(
                            $value,
                            $using,
                            $flags,
                            $curpath
                        )
                    );
                }
                else if(\Arr::isAssoc($value)) {
                    $dots = \Arr::merge($dots, \Arr::dotsByValue($value, $using, $flags, $curpath));
                }
                else {
                    $dots[$strpath] = $value;
                }
            }
            else {
                $dots[$strpath] = $value;
            }
        }

        return $dots;
    }

    /**
     * Converts an array to a dot array (dot separated, flat array) by reference.
     * 
     * @param array $array
     * @param int   $flags Flags to determine what compound values to evaluate
     * @param array $path  The path used for parameter passing to create the key in the associative array.
     * 
     * @return array
     */
    public static function dotsByReference (array|ArrayAccess &$array, string $using = ".", int $flags = \Arr::DOTS_EVAL_ASSOC, string|array $path = []): array {
        if(\Any::isString($path)) $path = \Str::split($path, $using);

        $dots = [];

        foreach($array as $key => &$value) {
            $path[] = $key;
            $strpath .= \Arr::join($path, ".");

            if(\Any::isArray($value)) {
                if($flags & \Arr::DOTS_EVAL_ARRAY) {
                    $dots = \Arr::merge(
                        $dots,
                        \Arr::dotsByValue(
                            $value,
                            $flags,
                            $path
                        )
                    );
                }
                else if(\Arr::isAssoc($value)) {
                    $dots = \Arr::merge($dots, \Arr::dotsByValue($value, $using, $flags, $path));
                }
                else {
                    $dots[$strpath] = $value;
                }
            }
            else {
                $dots[$strpath] = $value;
            }
        }

        return $dots;
    }

    /**
     * Gets the last entry of a given array by reference, optionally by a given filter.
     * 
     * @param array           $array
     * @param callable|string $filter
     * 
     * @return array
     */
    public static function endEntry (array|ArrayAccess $array, callable|string $filter = null): array {
        if($filter === NULL) {
            $lastKey = array_key_last($array);
            $lastValue = &$array[$lastKey];
        }
        else {
            foreach($array as $key => &$value) {
                if($filter($value, $key)) {
                    $lastKey = $key;
                    $lastValue = $value;
                }
            }
        }

        return [$lastKey, $lastValue];
    }

    /**
     * Gets the last value of a given array by reference, optionally by a given filter.
     * 
     * @param array           $array
     * @param callable|string $filter
     * 
     * @return mixed
     */
    public static function &end(array $array, callable|string $filter = null): mixed {
        return \Arr::endEntry($array, $filter)[1];
    }

    /**
     * On missing keys, call the callback provided.
     */
    public static function missing (array|ArrayAccess $array, array $keys, \Closure $callback): array {
        foreach($keys as $key) if(!\Arr::hasKey($array, $key)) $array[$key] = $callback($key);

        return $array;
    }

    /**
     * Find the key of a value in a given array.
     * 
     * @see array_search
     * 
     * @param array $array
     * @param mixed $value
     * @param bool  $strict
     * 
     * @return string|int
     */
    public static function find (array|ArrayAccess $array, $value, bool $strict = FALSE): string|int|bool {
        return array_search($value, $array, $strict);
    }

    /**
     * Find the keys of a value in a given array.
     * 
     * @param array           $array
     * 
     * @return string|int
     */
    public static function findAll($array, mixed $search, bool $strict = FALSE): array {
        $entries = [];

        foreach($array as $key => $value) {
            if($strict ? ($value === $search) : ($value == $search))
                $entries[] = $key;
        }

        return $entries;
    }

    /**
     * Check whether an array contains a given value (alias of in_array with reordered parameters)
     * 
     * @param array $array
     * @param mixed $value
     * 
     * @return bool
     */
    public static function contains(array|ArrayAccess $array, mixed $value): bool {
        return in_array($value, $array);
    }

    /**
     * Check whether an array has a given key (alias of array_key_exists with reordered parameters)
     * 
     * @param array            $array
     * @param string|int|array $key
     * 
     * @return bool
     */
    public static function hasKey (array|ArrayAccess $array, string|int $key): bool {
        return is_array($array) ? array_key_exists($key, $array) : $array->offsetExists($key);
    }

    /**
     * Check whether an array has a given keys
     * 
     * @param array $array
     * @param array $key
     * 
     * @return bool
     */
    public static function hasKeys (array|ArrayAccess $array, array $keys): bool {
        return \Arr::all(
            \Arr::map(
                $keys,
                fn($key) => array_key_exists($key, $array)
            )
        );
    }

    /**
     * Gets the first entry of a given array, optionally by a given filter.
     * 
     * @param array           $array
     * @param callable|string $filter
     * 
     * @return array
     */
    public static function firstEntry(array|ArrayAccess|Traversable $array, Closure $filter = null): array|null {
        if($filter === NULL && !(is_object($array) ? \Cls::implements($array, Traversable::class) : false)) {
            $firstKey = array_key_first($array);
            $firstValue = $array[$firstKey];
        }
        else {
            foreach($array as $key => $value) {
                if($filter ? $filter($value, $key) : true) {
                    $firstKey    = $key;
                    $firstValue  = $value;
                    break;
                }
            }
        }

        return $firstKey !== null ? [$firstKey, $firstValue] : null;
    }
    
    /**
     * Gets the first value of a given array, optionally by a given filter.
     * 
     * @param array           $array
     * @param callable|string $filter
     * 
     * @return mixed
     */
    public static function first (array|ArrayAccess|Traversable $array, Closure $filter = null): mixed {
        return ($entry = \Arr::firstEntry($array, $filter)) !== null ? $entry[1] : null;
    }

    /**
     * Pad the left side of an array by a given value and to a given length.
     * 
     * @param array $array
     * @param mixed $value
     * @param int   $length
     * 
     * @return array
     */
    public static function padLeft (array|ArrayAccess $array, mixed $value, int $length): array { 
        return array_pad($array, $length * -1, $value);
    }

    /**
     * Pad the right side of an array by a given value and to a given length.
     * 
     * @param array $array
     * @param mixed $value
     * @param int   $length
     * 
     * @return array
     */
    public static function padRight (array|ArrayAccess $array, $value, int $length): array { 
        return array_pad($array, $length, $value);
    }

    /**
     * Repeat a given value an N number of times.
     * 
     * @param mixed $value
     * @param int   $length
     * 
     * @return array
     */
    public static function repeat($value, int $length): array {
        return array_fill(0, $length, $value);
    }

    /**
     * Alias of 'range'
     * 
     * @param int|float $start
     * @param int|float $end
     * @param int|float $step
     * 
     * @return array
     */
    public static function arange(int|float $start, int|float $end, int|float $step = 1): array {
        return range($start, $end, $step);
    }

    /**
     * Alias of 'range' with no step option.
     * 
     * @param int|float $start
     * @param int|float $end
     * 
     * @return array
     */
    public static function range(int $start, int $end): array {
        return range($start, $end, 1);
    }

        /**
     * Check whether an array has a given key(s)
     * 
     * @param array            $array
     * @param string|int|array $key
     * 
     * @return bool
     */
    public static function has (array|ArrayAccess $array, string|int|array $key): bool {
        return \Any::isArray($key) ? \Arr::hasKeys($array, $key) : \Arr::hasKey($array, $key);
    }

    public static function getSingle (array|ArrayAccess $array, string $key, array $options = []): mixed {
        $fallback  = @$options["fallback"];
        $temporary = @$options["temporary"];
        $important = @$options["important"];
        $message   = @$options["message"];
        $validator = @$options["validator"];
        $converter = @$options["converter"];
        $cast      = @$options["cast"];

        $raisable  = $important === true && $fallback === null;

        // if($important !== null && !is_string($important) && !is_bool($important))
        //     throw new \Error("Important must be a message string or a boolean.");

        // $raisable  = ($important !== null && $fallback === null) ? $raisable : false;

        if(\Any::isString($cast)) {
            if($castClass = \Type::getByName($cast)) {
                $cast = $castClass;
            }

            if(\Cls::isSubclassOf($cast, \ScalarType::class)) {
                if($converter !== NULL) {
                    throw new Error("Converter and cast options are mutually exclusive.");
                }

                $converter = $cast."::parse";
            }
            else if(\Cls::isSubclassOf($cast, \CompoundType::class)) {
                throw new Error(
                    \Str::format(
                        "Compound type {type} cannot be casted.", 
                        [ "type" => $cast ]
                    )
                );
            }
        }

        $value = \Arr::hasKey($array, $key) ? @$array[$key] : null;

        if(\Any::isCallable($converter) && $value !== NULL) {
            $value = \Fnc::call($converter, [ $value ]);
        }

        if($value !== NULL) {
            if(\Any::isCallable($validator)) {
                if(\Fnc::call($validator, [ $value ]) !== true) {
                    throw new \Error(
                        \Str::format(
                            @$message["validation"] ?: "Value at offset '{key}' failed to validate.",
                            [ "key" => $key ]
                        )
                    );
                }
            }

            return $value;
        }

        if($raisable !== null && $raisable !== false) {
            throw new \UnexpectedValueException(
                \Str::format(
                    $raisable === true ? "Unable to get '{key}'." : ($raisable ?: @$message["get"]),
                    [ "key" => $key ]
                )
            );
        }

        return $fallback;
    }

    public static function getMultiSource (array|ArrayAccess $arrays, string $key, array $options = []): mixed {
        $important = @$options["important"];
        $fallback  = @$options["fallback"];
        $raisable  = $important === true && $fallback === null;

        if($important === true) {
            $options["important"] = false;
        }

        $options["fallback"] = null;

        foreach($arrays as $offset => $array) {
            if(\Arr::isAccessible($array)) {
                $value = \Arr::getSingle($array, $key, $options);

                if($value !== NULL) {
                    return $value;
                }
            }
            else {
                throw new \InvalidArgumentException(
                    \Str::format("Value at offset {} is not an array.", $offset)
                );
            }
        }

        if($raisable) {
            throw new \UnexpectedValueException(
                \Str::format(
                    "Unable to get '{}'.",
                    $key
                )
            );
        }

        return $fallback;
    }

    public static function xor (array|ArrayAccess $array): int {
        return \Arr::reduce(
            $array,
            fn($value, $accumulator) => $accumulator ^ $value,
            0
        );
    }

    public static function or (array|ArrayAccess $array): int {
        return \Arr::reduce(
            $array,
            fn($value, $accumulator) => $accumulator | $value,
            0
        );
    }

    public static function get (array|ArrayAccess $array, string $key, array $options = [], bool $multisource = false): mixed {
        return !$multisource ? \Arr::getSingle($array, $key, $options) : \Arr::getMultiSource($array, $key, $options);
    }

    public static function getsSingle (array|ArrayAccess $array, array $schema): array {
        $map = [];
        
        foreach($schema as $key => $options) {
            $map[$key] = \Arr::getSingle($array, $key, $options);
        }

        return $map;
    }

    public static function getsMultiSource (array|ArrayAccess $arrays, array $schema): array {
        $map = [];

        foreach($schema as $key => $options) {
            $value = \Arr::getMultiSource($arrays, $key, $options);

            if($value !== null) {
                $map[$key] = $value;
            }
        }

        return $map;
    }

    public static function gets (array|ArrayAccess $array, array $schema, bool $multisource = false): array {
        return !$multisource ? \Arr::getsSingle($array, $schema) : \Arr::getsMultiSource($array, $schema);
    }

    /**
     * Get the middle index in a given array.
     * 
     * @param array $array
     * @param int   $round  What method of rounding to find the middle index.
     * 
     * @return mixed
     */
    public static function middle (array|ArrayAccess $array, int $round = \Math::ROUND_HALF_UP): mixed {
        $count = count($array);

        if($round === \Math::ROUND_HALF_DOWN) $count--;

        $mid = \Math::ceil($count / 2);

        return $mid;
    }
 
    /**
     * Get the given offsets from an array and pass as arguments into the callback.
     *
     * @param  mixed $array
     * @param  mixed $offsets
     * @param  mixed $callback
     * @param  mixed $options
     * 
     * @return array|null
     */
    public static function use (array|ArrayAccess $array, string|array $offsets, callable $callback = null, array $options = []): array|null {
        if(\Any::isString($offsets)) {
            $offsets = [$offsets];
        }

        if(\Arr::isEmpty($offsets)) {
            $offsets = \Arr::keys($array);
        }

        $offsets = \Arr::mapAssoc(
            $offsets,
            function($offset, $value) use(&$array, &$options) {
                return [$value, \Arr::get($array, $value, $options)];
            }
        );

        if(\Any::isCallable($callback)) {
            \Fnc::call(
                $callback,
                \Arr::values($offsets)
            );
        }
        else {
            return $offsets;
        }

        return null;
    }

    /**
     * Modify entries witin an array using a given callback.
     * 
     * @param array        &$array 
     * @param string|array $offsets
     * @param callable     $callback
     * 
     * @return void
     */
    public static function modifyEntries (array|ArrayAccess &$array, string|array $offsets, callable $callback): void {
        if(!\Any::isArray($offsets)) {
            $offsets = [$offsets];
        }

        foreach($offsets as $index => $offset) {
            $value = $array[$offset];
            $entry = [$offset, $value];
            
            $entry = $callback($value, $offset);

            list($newOffset, $newValue) = $entry;

            if($offset !== $newOffset) {
                unset($array[$offset]);
            }

            $array[$newOffset] = $value;
        }
    }

    /**
     * Modify values witin an array using a given callback.
     * 
     * @param array        &$array 
     * @param string|array $offsets
     * @param callable     $callback
     * 
     * @return void
     */
    public static function modify (array|ArrayAccess &$array, string|array $offsets, callable $callback): void {
        if(!\Any::isArray($offsets)) {
            $offsets = [$offsets];
        }

        foreach($offsets as $index => $offset) {
            $value = &$array[$offset];
            $value = $callback($value, $offset);
        }
    }


    /**
     * Get the middle value in a given array.
     * 
     * @param array $array
     * @param int   $round  What method of rounding to find the middle index.
     * 
     * @return mixed
     */
    public static function centre (array|ArrayAccess $array, int $round = \Math::ROUND_HALF_UP): mixed {
        $mid = \Arr::middle($array, $round);

        return $array[$mid];
    }

    /**
     * Alias of 'array_slice'
     * 
     * @see array_slice
     * 
     * @param array $array
     * @param int   $offset
     * @param int   $length
     * 
     * @return array
     */
    public static function slice (array|ArrayAccess $array, int $offset, int $length = null): array {
        return array_slice($array, $offset, $length);
    }
    
    /**
     * An extended version of slice which allows for roll over and inverse slicing (if the start index is greater than the end, it will get all values except in the range)
     * 
     * @param array $array 
     * @param int   $start
     * @param ?int  $end    Unlike slice, there isn't use of lengths but a start and an end.
     * 
     * @return array
     */
    public static function subset (array|ArrayAccess $array, int $start, int $end = null): array {
        $length = \Arr::count($array);

        if($end === NULL) {
            return array_slice($array, $start, $length-$start);
        }
        else {
            if($start > $end) {

                $startToEnd = $length - $start;

                return \Arr::merge(
                    array_slice($array, $start, $startToEnd),
                    array_slice($array, 0, $end)
                );
            }
            else {
                $length = ($end - $start) + 1;

                return array_slice($array, $start, $length);
            }
        }
    }

    /**
     * Gets the last entry of a given array, optionally by a given filter.
     * 
     * @param array           $array
     * @param callable|string $filter
     * 
     * @return array
     */
    public static function lastEntry($array, Closure $filter = null): ?array {
        if($filter === NULL) {
            $lastKey = array_key_last($array);
            $lastValue = $array[$lastKey];
        }
        else {
            foreach($array as $key => $value) {
                if($filter($value, $key)) {
                    $lastKey = $key;
                    $lastValue = $value;
                }
            }
        }



        return $lastKey !== null ? [$lastKey, $lastValue] : null;
    }

    /**
     * Gets the last key of a given array, optionally by a given filter.
     * 
     * @param array           $array
     * @param callable|string $filter
     * 
     * @return string|int
     */
    public static function lastKey (array|ArrayAccess $array, callable|string $filter = null): string|int|null {
        return ($entry = \Arr::lastEntry($array, $filter)) !== null ? $entry[0] : null;
    }
    
    /**
     * Gets the last value of a given array, optionally by a given filter.
     * 
     * @param array           $array
     * @param callable|string $filter
     * 
     * @return mixed
     */
    public static function last (array|ArrayAccess $array, callable|string $filter = null): mixed {
        return ($entry = \Arr::lastEntry($array, $filter)) !== null ? $entry[1] : null;
    }

    /**
     * Get the values at a given depth in an array.
     * 
     * @param mixed $value
     * @param int   $required The depth required to get the values at
     * @param int   $depth Used for parameter passing, dont set.
     */
    public static function dive(mixed &$value, int $required, int $depth = 0): array|null {
        if($depth === $required) return[$value];
        
        
        $depth++;
        $levels = [];
        
        foreach($value as $subkey => $subvalue) {
            
            if(($level = \Arr::dive($subvalue, $required, $depth)) !== null) {
                if(\Any::isArray($level)) {
                    $levels = [...$levels, ...$level];
                }
                else {
                    $levels[] = $level;
                }
            }
        }
    
        if(count($levels) > 0)
            return $levels;

        return null;
    }

    /**
     * Turns strings into a nested associative path.
     * Eg. [ path, to, something ] turns into [ path => [ to => something ] ]
     */
    public static function drill(array $array, mixed $value): array {
        $aggregate = [];
        $last = &$aggregate;

        $array = [...$array, $value];
        
        $length = count($array);

        if($length === 1) {
            $array = ["0", ...$array];
            $length++;
        }

        if($length > 0) {
            for($index = 0; $index < $length-1; $index++) {
                $value = $array[$index];

                if(!\Arr::hasKey($last, $value)) {
                    $last[$value] = [];

                    $last = &$last[$value];
                }
            }

            $last = \Arr::last($array);
        }

        return $aggregate;
    }
    
    /**
     * Flip an array of rows in the Y-axis.
     * 
     * @param array $rows
     * 
     * @return array
     */
    public static function flipy (array|ArrayAccess $rows): array {
        return \Arr::map(
            $rows,
            function($row) {
                if(!\Any::isArray($row)) throw new \UnexpectedValueException("FlipY must be provided with a 2d array (rows).");

                return \Arr::flipx($row);
            }
        );
    }
    
    /**
     * Flip an array of rows in the X-axis.
     * 
     * @param array $rows
     * 
     * @return array
     */
    public static function flipx (array|ArrayAccess $rows): array {
        $height     = count($rows);
        $midptr     = $height / 2;
        $upperbound = (int)$midptr;
        $lowerbound = (int)$midptr;

        $middle = [];
        
        if(\Math::mod($midptr, 1.0) > 0.0) {
            $middle = [$rows[$midptr]];
            $upperbound++;
        }
        
        $upper  = \Arr::slice($rows, 0, $lowerbound);
        $lower  = \Arr::slice($rows, $upperbound, $height);

        return [...$lower, ...$middle, ...$upper];
    }

    /**
     * Convert an array to a normalised associative array.
     * 
     * @param array $array
     * @param mixed $default The default value to set the value to
     * 
     * @return array
     */
    public static function associate(
        array $array,
        $default,
        \Closure $collision = null,
        bool $deep = false,
        bool $strict = false
    ): array {
        $defaultType = \Any::getType($default);

        if(is_string($default) || is_int($default))
            throw new \UnexpectedValueException("Value type must not be string or integer.");

        $callback = function($offset, $value) use($defaultType, $default, $strict) {
            $valueType = \Any::getType($value);

            if($valueType !== $defaultType) {
                if(is_int($offset) && !is_string($offset)) {
                    if(!is_string($value) && !is_int($value)) {
                        throw new \InvalidArgumentException("Values that are to be turned into keys must be integers or strings.");
                    }

                    $offset = $value;
                    $value  = $default;
                }
                else if($strict === TRUE) {
                    $value = $default;
                }
            }

            return [$offset, $value];
        };

        if($deep) {
            \Arr::mapRecursive(
                $array,
                $callback
            );
    
            return $array;
        }
        else {
            return \Arr::mapAssoc(
                $array,
                $callback
            );
        }
    }

    /**
     * Nest an array within arrays to a given depth.
     * 
     * @param mixed $value
     * @param int   $depth Non-zero positive integer
     * 
     * @return array
     */
    public static function bury($value, int $depth = 1): array {
        if($depth < 1) throw new UnexpectedValueException("The depth must be a non-zero positive integer.");

        do {
            $value = [$value];

            $depth--;
        } while($depth !== 0);

        return $value;
    }

    /**
     * Move the slice of one array to another position.
     * 
     * @param array $array
     * @param int   $from
     * @param int   $to
     * 
     * @return array
     */
    public static function move (array|ArrayAccess $array, int $from, int $to): array {
        $element = $array[$from];

        $array = \Arr::splice($array, $from, $to - 1);
        $start = \Arr::subset($array, $from, $to);
        $start[] = $element;
        $end = \Arr::subset($array, $to);

        return \Arr::merge($start, $end);
    }
    
    /**
     * Alias of 'array_pop'
     */
    public static function pop (array|ArrayAccess &$array): mixed {
        return array_pop($array);
    }

    /**
     * Alias of 'sort' but by value, not by reference.
     */
    public static function sort (array|ArrayAccess $array, int $flags = SORT_REGULAR): mixed {
        sort($array, $flags);

        return $array;
    }

    /**
     * Cluster an array into custom groups.
     * 
     * @param array $array
     * @param callback $callback The clustering function, this must return a key for the cluster that the current value should be put into.
     * @param bool $preserve Whether to preserve th original entry's key or simply append onto the cluster
     * 
     * @return array
     */
    public static function cluster (array|ArrayAccess $array, callable $callback, bool $preserve = true): array {
        $clusters = [];

        foreach($array as $key => $value) {
            $cluster = $callback($value, $key);

            if($preserve)
                $clusters[$cluster][$key] = $value;
            else
                $clusters[$cluster][] = $value;
        }

        return $clusters;
    }

    /**
     * Alias of 'array_merge'
     */
    public static function merge (array|ArrayAccess ...$arrays): array {
        return array_merge(...$arrays);
    }

    /**
     * A different version of array_merge which preserves integer keys.
     */
    public static function mergePreserve(array|ArrayAccess ...$arrays): array {
        
        $buffer = [];

        foreach($arrays as $array) {
            foreach($array as $key => $value) {
                $buffer[$key] = $value;
            }
        }

        return $buffer;
    }

    /**
     * Alias of 'array_splice' but by-value.
     */
    public static function splice (array|ArrayAccess $array, int $offset = 0, int $length = null, $replacement = null): array {
        array_splice($array, $offset, $length);

        return $array;
    }
    
    /**
     * Turn an array into an associate one using the given keys.
     * 
     * @param array $array
     * @param array $keys   The keys that will be used, the index being the position in the value array to be replaced with a keyed entry
     * 
     * @return array
     */
    public static function key (array|ArrayAccess $array, array $keys): array {
        foreach($keys as $fromKey => $toKey) {
            $value = $array[$fromKey];

            unset($array[$fromKey]);

            $array[$toKey] = $value;
        }

        return $array;
    }

    /**
     * Map an array by its entries
     * 
     * @param array    $array
     * @param callable $callback
     * 
     * @return array
     */
    public static function mapAssoc (array|ArrayAccess $array, callable $callback): array {
        return \Arr::column(
            array_map(
                $callback,
                array_keys($array),
                $array
            ), 0, 1
        );
    }

    /**
     * An extended version of 'array_column' which, upon a null key, will append normally.
     * 
     * @param array $entries
     * @param int   $indexKey
     * @param int   $valueKey
     * 
     * @return array
     */
    public static function column (array|ArrayAccess $entries, int $indexKey, int $valueKey): array {
        $counter = 0;
        $array = [];

        foreach($entries as $entry) {
            $index  = $entry[$indexKey];
            $value  = $entry[$valueKey];

            if($index === null)
                $index = $counter++;

            $array[$index] = $value;
        }

        return $array;
    }

    /**
     * Alias of 'array_map' with reordered parameters.
     * 
     * @param array    ...$arrays
     * @param callable $callback
     */
    public static function map(): array {
        $arguments = \func_get_args();

        $arrays   = \Arr::slice($arguments, 0, -1);
        $callback = @\Arr::slice($arguments, -1)[0];

        if(\Arr::isEmpty($arrays)) {
            throw new \BadFunctionCallException("An array was not supplied to Arr::map().");
        }

        if(!\Any::isArray($callback) && $callback === null) {
            throw new \BadFunctionCallException("A callback was not supplied to Arr::map().");
        }

        if(is_string($callback) || is_int($callback) && !is_callable($callback)) {
            $callback = function($any) use($callback) {
                return \Any::isCompound($any) ? \Compound::get($any, $callback, null) : $any;
            };
        }

        return array_map($callback, ...$arrays);
    }

    /**
     * Alias of 'array_flip'
     */
    public static function flip (array|ArrayAccess $array): array {
        return array_flip($array);
    }

    /**
     * Get the median of an array.
     * 
     * @param array $array
     * 
     * @return mixed
     */
    public static function median (array|ArrayAccess $array): mixed {
        $array = \Arr::sort($array);
        $middle = \Arr::centre($array);

        return $middle;
    }

    /**
     * Alias of array_count_values
     */
    public static function tally (array|ArrayAccess $array): int {
        return array_count_values($array);
    }

    /**
     * Find the duplicates within an array.
     * 
     * @param array $array 
     * 
     * @return array An associatve array with the key => value of the duplicate item.
     */
    public static function duplicates($array): array {
        $unique     = [];
        $duplicates = [];

        foreach($array as $index => $value) {
            if(\Arr::contains($unique, $value)) {
                $duplicates[$index] = $value;
            }
            else {
                $unique[] = $value;
            }
        }

        return $duplicates;
    }

    /**
     * Get only the keys specific for an array.
     * 
     * @param array $array
     * @param array $keys
     * 
     * @return array
     */
    public static function only($array, array $keys): array {
        $intermediate = [];

        foreach($keys as $key) {
            if(\Arr::hasKey($array, $key))
                $intermediate[$key] = $array[$key];
        }

        return $intermediate;
    }

    /**
     * Get all keys of an array except specified.
     * 
     * @param array $array
     * @param array $keys
     * 
     * @return array
     */
    public static function except($array, array|string $keys): array {
        if(is_string($keys)) $keys = [$keys];

        foreach($keys as $key) unset($array[$key]);

        return $array;
    }

    /**
     * Calculate the mean of an array.
     * 
     * @param array $array
     * 
     * @return int|float
     */
    public static function mean (array|ArrayAccess $array): int|float {
        return (array_sum($array) / count($array));
    }

    /**
     * Alias of 'min'
     */
    public static function min (array|ArrayAccess $array, array ...$arrays): int {
        return min($array, ...$arrays);
    }

    /**
     * Alias of 'max'
     */
    public static function max (array|ArrayAccess $array, array ...$arrays): int {
        return max($array, ...$arrays);
    }

    /**
     * Alias of 'array_sum'
     */
    public static function sum (array|ArrayAccess $array): int {
        return array_sum($array);
    }
    
    /**
     * Subtract the values of multiple arrays.
     * 
     * @param array ...$arrays
     * 
     * @return array
     */
    public static function subtract (array|ArrayAccess ...$arrays): array {
        $lastValue = null;

        $arrayDeltas = [];

        foreach($arrays as $currentValueIndex => $currentValue) {
            if($lastValue !== NULL) {
                $pairArrayDeltas = [];

                if(\Any::isArray($currentValue)) {
                    foreach($currentValue as $currentValueSubIndex => $currentValueSubValue) {
                        $lastValueSubValue = $lastValue[$currentValueSubIndex];

                        if($lastValueSubValue === NULL) {
                            $lastValueSubValue = 0;
                        }

                        $currentValueSubValueDelta = $lastValueSubValue - $currentValueSubValue;

                        $pairArrayDeltas[$currentValueSubIndex] += $currentValueSubValueDelta;
                    }
                }

                $arrayDeltas[] = $pairArrayDeltas;
            }

            $lastValue = $currentValue;
        }

        return $arrayDeltas;
    }

    /**
     * Gets the first entry of a given array by reference, optionally by a given filter.
     * 
     * @param array           $array
     * @param callable|string $filter
     * 
     * @return array
     */
    public static function startEntry (array|ArrayAccess $array, callable|string $filter = null): array {
        if($filter === NULL) {
            $firstKey = array_key_first($array);
            $firstValue = &$array[$firstKey];
        }
        else {
            foreach($array as $key => &$value) {
                if($filter($value, $key)) {
                    $firstKey = $key;
                    $firstValue = $value;
                    break;
                }
            }
        }

        return [$firstKey, $firstValue];
    }

    /**
     * Gets the first value of a given array by reference, optionally by a given filter.
     * 
     * @param array           $array
     * @param callable|string $filter
     * 
     * @return array
     */
    public static function &start(array $array, callable|string $filter = null) {
        return \Arr::startEntry($array, $filter)[1];
    }

    /**
     * A variant of 'array_column' which upon duplicate keys, will store their values together in an array.
     * 
     * @param array $array
     * @param int   $keyIndex
     * @param int   $valueIndex 
     * 
     * @return array
     */
    public static function propogateColumn (array|ArrayAccess $array, int $keyIndex, int $valueIndex): array {
        $assoc = [];

        foreach($array as $entry) {
            list($key, $value) = $entry;

            if(!(\Any::isString($key) || \Any::isInt($key)))
                throw new \Error("Keys must be integers or strings.");

            $assoc[$key][] = $value;
        }

        return \Arr::map($assoc, function($value) {
            return count($value) > 1 ? $value : $value[0];
        });
    }


    /**
     * Remove the keys of an array and order it.
     * 
     * @param array $array
     * @param array $order The keys to order by.
     * 
     * @return array
     */
    public static function unkey (array|ArrayAccess $array, array $order): array {
        $output = [];

        foreach($order as $key) {
            $output[] = $array[$key];
        }

        return $output;
    }


    /**
     * Change the order of an associative array.
     * 
     * @param array $array
     * @param array $order The keys to order by.
     * 
     * @return array
     */
    public static function rekey (array|ArrayAccess $array, array $order, \Closure $missing = null): array {
        $output = [];

        foreach($order as $key) {
            $output[$key] = @$array[$key] === null && $missing !== null ? $missing($key) : $array[$key];
        }

        return $output;
    }


    /**
     * Change the order of an associative array.
     * 
     * @param array $array
     * @param array $order The keys to order by.
     * 
     * @return array
     */
    public static function rearrange (array|ArrayAccess $array, array $order, \Closure $missing = null): array {
        $output = [];

        foreach($order as $from => $to) {
            $output[$to] = @$array[$from] === null && $missing !== null ? $missing($to) : $array[$from];
        }

        return $output;
    }

    /**
     * Flatten an array.
     * 
     * @param array $array
     * @param bool  $preserve Whether to preserve keys
     * 
     * @return array
     */
    public static function flatten (array|ArrayAccess $array, bool $preserve = false): array {
        $accumulator = [];

        array_walk_recursive(
            $array,
            function($value, $key) use (&$accumulator, &$preserve) {
                if($preserve) $accumulator[$key] = $value;
                else $accumulator[] = $value;
            }
        );

        return $accumulator;
    }

    /**
     * Get a generator of the array's entries.
     * 
     * @param array $array
     * @param bool  $generator
     * 
     * @return Generator|array
     */
    public static function entries (array|ArrayAccess $array, bool $generator = false): \Generator|array {
        if($generator)
            return \Arr::entriesGenerator($array);

        $entries = [];

        foreach($array as $offset => $value) {
            $entries[] = [$offset, $value];
        }

        return $entries;
    }

    /**
     * Get the entries of an array as a generator.
     * 
     * @param array $array
     * 
     * @return Generator
     */
    public static function entriesGenerator (array|ArrayAccess $array): \Generator {
        foreach($array as $offset => $value) yield [$offset, $value];       
    }

    /**
     * Reverse the categorising seen by outputs like preg_match where fields are grouped together.
     * 
     * @param array $array
     * 
     * @return array
     */
    public static function decategorise (array|ArrayAccess $array): array {
        return \Arr::reduce(
            \Arr::entries($array),
            function($aggregate, $accumulator) {
                foreach($accumulator[1] as $index => $value) {
                    $aggregate[$index][$accumulator[0]] = $value;
                }
        
                return $aggregate;
            },
            []
        );
    }

    /**
     * Interlace two arrays' elements, one after another.
     * 
     * @param array $shoe
     * @param array $lace
     * 
     * @return array
     */
    public static function interlace (array|ArrayAccess $shoe, array $lace): array {
        $aggregate = [];

        $shoeSize = count($shoe);
        $laceSize = count($lace);
        $aggrSize = $shoeSize > $laceSize ? $shoeSize : $laceSize;

        for($aggrIndex = 0; $aggrIndex < $aggrSize; $aggrIndex++) {
            $aggregate[] = @$shoe[$aggrIndex];
            $aggregate[] = @$lace[$aggrIndex];
        }

        return $aggregate;
    }

    /**
     * Get elements for a given interval.
     * 
     * @param array     $array
     * @param int       $interval The interval to get values at.
     *                            If you set the interval at 1, it will assume you mean the first
     *                            element and every third element (plus the end if its index is a
     *                            multiple of three).
     * 
     * @return array
     */
    public static function every (array|ArrayAccess $array, int $interval): array {
        $nth = [];
        $rest = [];
        $index = 0;
        $keys = array_keys($array);

        if($interval === 1) {
            $nth[]    = $array[$keys[$index++]];
            $interval = 3;
        }

        for(; $index < count($array); $index++) {
            if(($index + 1) % $interval === 0) {
                $nth[] = $array[$keys[$index]];
            }
            else {
                $rest[] = $array[$keys[$index]];
            }
        }

        return $nth;
    }

    /**
     * Map an array by its nth term.
     * 
     * @param array       $array 
     * @param int|Closure $interval
     * @param Closure     $callback
     * 
     * @return array
     */
    public static function nmap($array, int|\Closure $interval, \Closure $callback): array {
        if($interval instanceof \Closure)
            $interval = $interval();

        for($index = 0; $index < count($array); $index++) {
            if(($index + 1) % $interval === 0) {
                
                $replacement = $callback($array[$index]);

                $array[$index] = $replacement;
            }
        }

        return $array;
    }

    /**
     * Get the types within a given array.
     * 
     * @param array $array
     * @param array $arguments
     * 
     * @return array
     */
    public static function getTypes (array|ArrayAccess $array, array $arguments = []): array {
        return \Arr::map(
            $array, function($value) use(&$arguments) {
                return \Fnc::call([\Any::class, "getType"], \Arr::merge([$value], $arguments));
            }
        );
    }

    /**
     * @see array_unique
     */
    public static function unique (array|ArrayAccess $array, int $flags = SORT_STRING): mixed {
        return array_unique($array, $flags);
    }

    /**
     * @see array_keys
     */
    public static function keys (array|ArrayAccess $array): array {
        return array_keys($array);
    }

    /**
     * @see array_values
     */
    public static function values (array|ArrayAccess $array): array {
        return array_values($array);
    }

    /**
     * @see array_filter
     */
    public static function filter (array|ArrayAccess $array, Closure|string $callback = null, int $flag = 0): array {
        if($callback === null)
            $callback = Closure::fromCallable('is_set');

        if(is_string($callback)) {
            $key = $callback;

            if(\Integer::hasBits($flag, \Arr::FILTER_BOTH)) {
                $callback = function($index, $compound) use($key) {
                    return \Compound::get($compound, $key);
                };
            }
            else if(\Integer::hasBits($flag, \Arr::FILTER_VALUE) || $flag === 0) {
                $callback = function($compound) use($key) {
                    return \Compound::get($compound, $key);
                };
            }
            else {
                throw new \Error();
            }

            
        }

        return array_filter($array, $callback, $flag);
    }

    /**
     * @see array_reduce
     */
    public static function reduce (array|ArrayAccess $array, callable $callback, $initial = NULL): mixed {
        return array_reduce($array, $callback, $initial);
    }

    /**
     * @see array_reverse
     */
    public static function reverse (array|ArrayAccess $array, bool $preserve = false): array {
        return array_reverse($array, $preserve);
    }

    /**
     * Alias of 'implode'
     * 
     * @see implode 
     * 
     * @param array $array
     * @param string $glue
     * 
     * @return string
     */
    public static function join (array|ArrayAccess $array, string $glue = ""): string {
        return implode(
            $glue,
            \Arr::values(\Arr::filter(
                $array,
                function($value) {
                    return $value !== NULL;
                }
            ))
        );
    }

    /**
     * Gives all possible chunks of a given within an an array which
     * overlap.
     * 
     * @param array $array
     * @param int   $chunk
     * 
     * @return array
     */
    public static function interchunk (array|ArrayAccess $array, int $chunk): array {
        $length = ((count($array)+1) - $chunk);
        $aggregates = [ ];

        for($index = 0; $index < $length; $aggregates[] = \Arr::slice($array, $index++, $chunk));

        return $aggregates;
    }

    /**
     * Chunk an array by a given size.
     * 
     * @param array $array
     * @param int   $interval
     */
    public static function chunk (array|ArrayAccess $array, int $interval): array {
        $chunks = [];

        for($index = 0; $index < count($array); $index++) {
            if($index % $interval === 0) {
                $chunks[] = \Arr::slice($array, $index, $interval);
            }
        }

        return $chunks;
    }

    /**
     * Map an array so each element will contain N items in front, behind, or both (their 'neighbourhood').
     * 
     * @param array $array
     * @param int   $look
     * @param bool  $preserve
     * 
     * @return array
     */
    public static function neighbours($array, int|array $look, bool $preserve = false): array {
        if(is_int($look)) $look = [$look, $look];
        else if(count($look) !== 2)
            throw new \Error("There must be two forward and back looking sizes.");

        $size = \Arr::sum($look)+1;

        $backwardLook = $look[0];
        $forwardLook  = $look[1];

        $arraySize = count($array);

        $aggrNeighbourhoods = [];

        for($currentIndex = 0; $currentIndex < $arraySize; $currentIndex++) {
            $currentNeighbourhood = [];        

            if(($currentIndex >= $backwardLook) && ($currentIndex < ($arraySize-$forwardLook))) {
                for($neighbourhoodIndex = ($currentIndex - $backwardLook); $neighbourhoodIndex <= ($currentIndex+$forwardLook); $neighbourhoodIndex++) {
                    if($preserve)
                        $currentNeighbourhood[$neighbourhoodIndex] = $array[$neighbourhoodIndex];
                    else
                        $currentNeighbourhood[] = $array[$neighbourhoodIndex];
                }

                $aggrNeighbourhoods[] = $currentNeighbourhood;
            }

        }

        return $aggrNeighbourhoods;
    }

    /**
     * Convert an array into a set of the relationships between values and their neighbours.
     * 
     * Number of leads for a given length is 2n-2.
     * 
     * @param array $array
     * 
     * @return array
     */
    public static function lead (array|ArrayAccess|\Generator $array, bool $generator = false): \Generator|array {
        $groups = \Arr::leadGenerator($array);

        

        return !$generator ? iterator_to_array($groups) : $groups;
    }

    public static function leadGenerator (array|ArrayAccess|Generator $array): \Generator {
        $arraySize = \Arr::count($array);

        $lastIndex = 0;

        for($currentIndex = 1; $currentIndex < $arraySize; $currentIndex++) {
            yield [$array[$lastIndex], $array[$currentIndex]];

            $lastIndex = $currentIndex;
        }
    }

    /**
     * Tokenise an array into tokens provided.
     * 
     * @param array $array 
     * @param array $tokens 
     * @param int   $mode
     * 
     * @return array
     */
    public static function tokenise (array|ArrayAccess $array, array $tokens = null, int $mode = \Arr::TOKENIZE_NORMAL): array {
        if($tokens === NULL) {
            $tokens = \Arr::splice(
                \Arr::unique(
                    \Arr::values($array)
                )
            );
        }

        return [
            \Arr::map(
                $array,
                function($arrayValue) use($tokens, $mode) {
                    foreach($tokens as $tokenKey => $tokenValue) {
                        if($arrayValue == $tokenValue) {
                            return $tokenKey;
                        }
                    }

                    if($mode === \Arr::TOKENIZE_NORMAL) {
                        return $arrayValue;
                    }
                }
            ),
            $tokens
        ];
    }

    
    /**
     * Map all the values (not including arrays) at a given depth.
     * 
     * @param array        $array
     * @param callable     $callback
     * @param callable|int $required The depth that values should be mapped at.
     * 
     * @return void
     */
    public static function mapDepth (array|ArrayAccess &$array, callable $callback, int|callable $required, int $depth = 0): void {
        if(is_int($required)) {
            $required = function($key, $value, $depth) use($required) {
                return $depth === $required;
            };
        }

        foreach($array as $key => &$value) {
            if($required($key, $value, $depth)) {
                $callback($key, $value, $depth);
            }
            else if(is_array($value)) {
                \Arr::mapDepth($value, $required, $required, $depth+1);
            }
        }
    }

    /**
     * Map the values of an array recursively (by reference).
     * 
     * @param array    $array
     * @param callable $callback 
     * @param int      $depth Used for internal usage, no not set.
     * 
     * @return void
     */
    public static function mapRecursive (array|ArrayAccess &$array, callable $callback, int $depth = 0): void {
        foreach($array as $key => $value) {
            if(is_array($value)) {
                \Arr::mapRecursive($array[$key], $callback, $depth+1);
            }
            else {
                unset($array[$key]);
                list($newKey, $newValue) = $callback($key, $value, $depth);

                $array[$newKey] = $newValue;
            }
        }
    }

    /**
     * Map the values of an array recursively (by reference).
     * 
     * @param array    $array
     * @param callable $callback 
     * @param int      $depth Used for internal usage, no not set.
     * 
     * @return void
     */
    public static function mapRecursiveOnly (array|ArrayAccess &$array, callable $callback, callable $filter, int $type = \Arr::RECURSIVE_TOP_DOWN, int $depth = 0): void {
        foreach($array as $key => $value) {
            $map = $filter($key, $value);

            if($type === \Arr::RECURSIVE_BOTTOM_UP && is_array($value)) {
                \Arr::mapRecursiveOnly($array[$key], $callback, $filter, $type, $depth+1);
            }

            if($map) {
                unset($array[$key]);
                list($key, $value) = $callback($key, $value, $depth);
    
                $array[$key] = $value;
            }


            if($type === \Arr::RECURSIVE_TOP_DOWN && is_array($value)) {
                \Arr::mapRecursiveOnly($array[$key], $callback, $filter, $type, $depth+1);
            }            
        }
    }

    /**
     * Walking the values of an array recursively
     * 
     * @param array    $array
     * @param callable $callback 
     * @param int      $depth Used for internal usage, no not set.
     * 
     * @return void
     */
    public static function walkRecursive (array|ArrayAccess $array, callable $callback, int $depth = 0): void {
        foreach($array as $key => $value) {
            if(is_array($value)) {
                \Arr::walkRecursive($value, $callback, $depth+1);
            }
            else {
                $callback($key, $value, $depth);
            }
        }
    }

    /**
     * Match the depth of two or more arrays.
     * 
     * @param array ...$arrays
     * 
     * @return array
     */
    public static function matchDepths (array|ArrayAccess ...$arrays): \Generator {
        $depths = \Arr::map(
            $arrays,
            function($array){
                return \Arr::depthOf($array);
            }
        );
        
        $maxDepth = \Arr::max($depths);

        foreach($arrays as $index => $array) {
            $depth = $depths[$index];

            if($depth < $maxDepth) {
                yield \Arr::bury($array, $maxDepth - $depth);
            }
            else {
                yield $array;
            }
        }
    }
}

?>