<?php

/** Compound Type and Functions */
abstract class Compound extends DataType {
    

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

            if($length === 1) {
                unset($compound[$path[0]]);

                return true;
            }
            else{
                $target = &$compound[$path[0]];

                if(\Any::isCompound($target))
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
     * @return object
     */
    public static function set(object|array &$compound, array|string $path, mixed $value, object|array|null $fallback = null): bool {
        if(is_string($path))
            $path = \Str::split($path, ".");

        $length = count($path);

        if($length > 0) {
            $target = &$compound[$path[0]];

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
        if(is_string($path))
            $path = \Str::split($path, ".");

        $length = count($path);

        if($length > 0) {
            $keyed = false;

            if(is_object($compound)) {
                $keyed = property_exists($compound, $path[0]);
                $value = &$compound->{$path[0]};
            }
            else if(is_array($compound)) {
                $keyed = \Arr::hasKey($compound, $path[0]);
                $value = &$compound[$path[0]];
            }

            if($length === 1 && $keyed) {
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

        return \Any::copy($value);
    }
}

?>