<?php

class Boolean extends ScalarType implements Slate\Data\Contract\ISizeStaticallyAttainable {
    const NAMES            = ["bool", "boolean"];
    const VALIDATOR        = "is_bool";
    const CONVERTER        = "boolval";

    /**
     * @see Slate\Data\ISizeStaticallyAttainable::getSize()
     */
    public static function getSize():int {
        return 8;
    }

    /**
     * Parse a boolean.
     * 
     * @param mixed $value 
     * @param bool  $strict Whether to take the value verbatim or preprocess to make the value more parsable.
     * 
     * @return bool|null
     */
    public static function parse($value, bool $strict = false): bool|null {
        if(is_bool($value)) {
            return $value;
        }
        if(is_string($value)) {
            if(!$strict) 
                $value = \Str::lowercase($value);

            if($value == "true" || $value == 1) {
                return true;
            }
            else if($value == "false" || $value == 0  || $value == "")  {
                return false;
            }
        }
        else if(is_numeric($value)) {
            if($value == 1) {
                return true;
            }
            else if($value == 0) {
                return false;
            }
        }

        return null;
    }

    /**
     * Try and parse the value and upon failure, throw an exception.
     * 
     * @throws Slate\Exception\ParseException
     * 
     * @param mixed $value
     * 
     * @return bool
     */
    public static function tryparse(mixed $value): bool {
        $value = \Boolean::parse($value);

        if($value === NULL) {
            throw new Slate\Exception\ParseException("Unable to parse boolean value.");
        }

        return $value;
    }
}

?>