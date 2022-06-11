<?php

/**
 * Compound Type Class with Helper Definitions
 */
abstract class Compound extends DataType {

    public static function has(object|array &$compound, string $key) {
        return is_object($compound) ? \Obj::hasProperty($compound, $key) : \Arr::hasKey($compound, $key);
    }

    public static function walkRecursiveThrough(object|array &$compound, array|string $through, Closure $callback) {
        foreach(static::keys($compound) as $key) {
            if(is_string($through))
                $through = \Str::split($through, ".");

            $fellback = false;

            $value = \Compound::get($compound, [$key, ...$through], null, $fellback);

            if(!$fellback)
                $callback($key, $value);

            if(\Any::isCompound($value))
                static::walkRecursiveThrough($value, $through, $callback);
        }
    }

    /**
     * Modify a compound value recursively using a callback.
     *
     * @param object|array $compound
     * @param Closure $callback
     * @param string $type
     * @param array $path
     *
     * @return void
     */
    public static function modifyRecursive(object|array &$compound, Closure $callback, string $type = "bottom-top", array $path = []) {
        foreach(static::keys($compound) as $key) {
            if(is_array($compound)) $value = &$compound[$key];
            else $value = &$compound->{$key};

            if(\Any::isCompound($value) ? $type === "bottom-up" : false)
                \Compound::modifyRecursive($value, $callback, $type, [...$path, $key]);

            $callback($key, $value, [...$path, $key]);

            if(\Any::isCompound($value) ? $type === "top-down" : false)
                \Compound::modifyRecursive($value, $callback, $type, [...$path, $key]);
        }
    }

    public static function keys(object|array $compound) {
        return \Arr::keys(is_object($compound) ? get_object_vars($compound) : $compound);
    }

    /**
     * Used to safely set a nested value in an array or object.
     *
     * @param array|object $subject
     * @param array|string $path
     * @param mixed        $value
     * 
     * @return object
     */
    public static function unset(object|array &$compound, array|string $path): bool {
        if(is_string($path))
            $path = \Str::split($path, ".");

        $length = count($path);

        if($length > 0) {
            if(is_object($compound)) {
                $target = &$compound->{$path[0]};
            }
            else if(is_array($compound)) {
                $target = &$compound[$path[0]];
            }

            if($length === 1) {
                unset($target);

                return true;
            }
            else if(\Any::isCompound($target)) {
                return \Compound::unset($target, \Arr::slice($path, 1));
            }
        }

        return false;
    }

    /**
     * Used to safely set a nested value in an array or object.
     *
     * @param array|object $subject
     * @param array|string $path
     * @param mixed        $value
     * 
     * @return bool
     */
    public static function set(object|array &$compound, array|string $path, mixed $value, object|array|null $fallback = []): bool {
        if(is_string($path)) {
            $path = \Str::split($path, ".");
        }

        $length = count($path);

        if($length > 0) {
            if(is_object($compound)) {
                $target = &$compound->{$path[0]};
            }
            else if(is_array($compound)) {
                $target = &$compound[$path[0]];
            }

            if($length === 1) {
                $target = $value;

                return true;
            }
            else{
                if($target === null && $fallback !== null) {
                    $target = $fallback;
                }

                if(\Any::isCompound($target))
                    return \Compound::set($target, \Arr::slice($path, 1), $value, $fallback);
            }
        }

        return false;
    }

    /**
     * Used to safely get a nested value in an object or array.
     *
     * @param array|object $subject
     * @param array|string $path
     * @param mixed        $fallback
     * 
     * @return object
     */
    public static function &get(object|array &$compound, array|string $path, mixed $fallback = null, bool &$fellback = false): mixed {
        if(is_string($path)) {
            $path = \Str::split($path, ".");
        }

        $length = count($path);

        if($length > 0) {
            $keyed = \Compound::has($compound, $path[0]);

            if(is_object($compound)) {
                $value = &$compound->{$path[0]};
            }
            else if(is_array($compound)) {
                $value = &$compound[$path[0]];
            }

            if($length === 1 && ($keyed)) {
                $fellback = false;

                return $value;
            }
            else if(is_array($value) || is_object($value)) {
                return \Compound::get($value, \Arr::slice($path, 1), $fallback, $fellback);
            }
        }

        $fellback = true;

        return $fallback;
    }

    /**
     * Used to safely get a nested value in an object or array then unset its value.
     *
     * @param array|object $subject
     * @param array|string $path
     * @param mixed        $fallback
     * 
     * @return object
     */
    public static function take(object|array &$compound, array|string $path, mixed $fallback = null, &$fellback = null): mixed {
        $value = \Compound::get($compound, $path, $fallback, $fellback);

        if(!$fellback)
            \Compound::unset($compound, $path);

        return $value;
    }
}

?>