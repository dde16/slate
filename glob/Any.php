<?php
abstract class Any {
    use \Slate\Utility\TSplFacade;
    
    /**
     * The function mapping for this
     * facade to its standard php library
     * equivalent.
     */
    public const SPL = [
        // "getType"      => "gettype",
        "isEmpty"      => "empty",
        "isString"     => "is_string",
        "isObject"     => "is_object",
        "isInt"        => "is_int",
        "isInteger"    => "is_int",
        "isNumeric"    => "is_numeric",
        "isBool"       => "is_bool",
        "isFloat"      => "is_float",
        "isCountable"  => "is_countable",
        "isDouble"     => "is_double",
        "isLong"       => "is_long",
        "isFinite"     => "is_finite",
        "isCallable"   => "is_callable",
        "isScalar"     => "is_scalar",
        "isArray"      => "is_array",
        "isResource"   => "is_resource",
        "isWritable"   => "is_writable",
        "isIterable"   => "is_iterable",
        "isInfinite"   => "is_infinite",
        "isNull"       => "is_null",
        "isNaN"        => "is_nan",
        "isExecutable" => "is_executable",
        "isLink"       => "is_link",
        "isReadable"   => "is_readable",
        "isWriteable"  => "is_writeable"
    ];

    /**
     * Perform a shallow copy for values taken by reference.
     * 
     * @param mixed $value
     * 
     * @return mixed
     */
    public static function copy(mixed $value): mixed {
        return $value;
    }

    /**
     * Get the type of a given value with greater depth than the native function.
     * 
     * @param mixed $value
     * 
     * @param bool  $verbose  Whether to given more information of the type of value is provided.
     *                        such as the class (for an object) or the type if a resource.
     * 
     * @param bool  $tokenise Get the Type Id
     * @param bool  $natives 
     * 
     * @return array|int|string
     */
    public static function getType(mixed $value, bool $verbose = false, bool $tokenise = false, bool $natives = true): string|array {
        $rootType = \Str::lowercase(gettype($value));

        if($natives) {
            $rootTypeClass = \Type::getByName($rootType);

            if($rootTypeClass !== null) {
                $rootType = $rootTypeClass::NAMES[0];
            }
        }

        if($tokenise)
            $rootType = \Type::getValue(\Str::uppercase($rootType));

        $subTypes = [];

        if($verbose) {
            switch($rootType) {
                case \Type::OBJECT:
                case "object":
                    $subTypes[] = get_class($value);
                    break;
                case "resource":
                    $subTypes[] = \Rsc::getType($value);
                    break;
            }
        }

        return !$verbose ? $rootType : [$rootType, ...$subTypes];
    }
    
    /**
     * Checks whether a given value is a compound type (array or object)
     * 
     * @param mixed $value
     *
     * @return bool
     */
    public static function isCompound(mixed $value): bool {
        return \Arr::any(
            \Cls::getSubclassesOf(\CompoundType::class),
            function($class) use (&$value){
                return $class::validate($value);
            }
        );
    }

    /**
     * Convert a decimal number to binary, including real numbers.
     * 
     * @param int|float|double $number
     * 
     * @return string
     */
    public static function dec2bin(int|float $number): string {
        $bytes = [];

        while ($number >= 256) {
            $bytes[] = (($number / 256) - (floor($number / 256))) * 256;
            $number = floor($number / 256);
        }

        $bytes[] = $number;
        $binstring = "";

        for ($i = 0; $i < count($bytes); $i++) {
            $binstring = (($i == count($bytes) - 1) ? decbin($bytes[$i]) : str_pad(decbin($bytes[$i]), 8, "0", STR_PAD_LEFT)).$binstring;
        }

        return $binstring;
    }
}
