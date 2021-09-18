<?php

class Type extends \Slate\Structure\Enum {
    private static $classes = [
        Type::ARR     => \Arr::class,
        Type::ARRAY   => \Arr::class,
        Type::STR     => \Str::class,
        Type::STRING  => \Str::class,
        Type::INT     => \Integer::class,
        Type::INTEGER => \Integer::class,
        Type::OBJ     => \Obj::class,
        Type::OBJECT  => \Obj::class,
        Type::FLOAT   => \Real::class,
        Type::REAL    => \Real::class,
        Type::DOUBLE  => \Real::class,
        Type::BOOL    => \Boolean::class,
        Type::BOOLEAN => \Boolean::class
    ];

    const ARR       = 1;
    const ARRAY     = 1;
    const STR       = 2;
    const STRING    = 2;
    const INT       = 4;
    const INTEGER   = 4;
    const OBJ       = 8;
    const OBJECT    = 8;
    const FLOAT     = 16;
    const REAL      = 16;
    const DOUBLE    = 16;
    const BOOL      = 32;
    const BOOLEAN   = 32;

    public static function getById(int $id): string|null {
        return @static::$classes[$id];
    }

    public static function getByName(string $name): string|null {
        foreach(static::$classes as $class) {
            $names = Cls::getConstant($class, "NAMES");

            if(Any::isArray($names)) {
                if(Arr::contains($names, \Str::lowercase($name))) {
                    return $class;
                }
            }
        }

        return null;
    }
}

?>