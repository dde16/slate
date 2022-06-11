<?php

class Any extends DataType {
    public static function validate($value): bool {
        return true;
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
        return is_object($value) || is_array($value);
    }
}
