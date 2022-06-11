<?php declare(strict_types=1);

abstract class ScalarType extends DataType {
    public static function parse(mixed $value): mixed {
        if (!static::validate($value)) {
            $converter = \Cls::getConstant(static::class, "CONVERTER");

            if ($converter === FALSE) {
                throw new Error("Type {$value} does not have a converter/parser function.");
            }

            $converter = Closure::fromCallable($converter);

            return $converter($value);
        }

        return $value;
    }

    public static function tryparse(mixed $value): mixed {
        $parsed = static::parse($value);

        if ($parsed === null) {
            throw new \Slate\Exception\ParseException(
                "Unable to parse value '{$value}' for type " . static::NAMES[0] . "."
            );
        }

        return $parsed;
    }
}

?>