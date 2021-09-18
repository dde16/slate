<?php

final class Obj extends CompoundType {
    const NAMES            = ["object"];
    const VALIDATOR        = "is_object";

    public static function map(object $object, Closure $callback): object {
        foreach(get_object_vars($object) as $key => $value) {
            $object->{$key} = $callback($value, $key);
        }
        return $object;
    }

    public static function hasProperty(object $object, string $property): bool {
        return property_exists($object, $property);
    }

    public static function set(object &$object, array|string $path, mixed $value): bool {
        return \Compound::set($object, $path, $value);
    }

    public static function get(object &$object, array|string $path, mixed $fallback = null): mixed {
        return \Compound::get($object, $path, $fallback);
    }

    public static function fromArray(array $array, bool $recursive = false): \stdClass {
        $instance = new \stdClass();

        if($recursive) {
            \Arr::mapRecursiveOnly($array, function($key, $array) {
                return [$key, \Obj::fromArray($array, recursive: false)];
            }, function($value) {
                return is_array($value) ? \Arr::isAssoc($value) : false;
            });
        }
        
        foreach($array as $key => $value)
            $instance->{$key} = $value;

        return $instance;
    }

    public static function values(object $object): array {
        return \Arr::values(\Obj::toArray($object));
    }

    public static function keys(object $object): array {
        return \Arr::values(get_object_vars($object));
    }

    public static function toArray(object $object): array {
        return get_object_vars($object);
    }

    public static function entries(object $object, bool $generator  =false): array|\Generator {
        return \Arr::entries(get_object_vars($object), $generator);
    }
}

?>