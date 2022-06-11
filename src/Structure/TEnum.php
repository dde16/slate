<?php declare(strict_types = 1);

namespace Slate\Structure {
    use ReflectionClass;
    use RuntimeException;

    trait TEnum {
        /**
         * Ensure the class is not instantiable by default.
         */
        private function __construct() {}

        /**
         * Get all of the values for this enum.
         * 
         * @return array
         */
        public static function getValues(): array {
            return (new ReflectionClass(static::class))->getConstants();
        }

        /**
         * Check if a constant value exists.
         * 
         * @param array|string|bool|int|float $value Value to search for
         * 
         * @return bool
         */
        public static function hasValue(array|string|bool|int|float $value): bool {
            if ($constants = \Cls::getConstants(static::class)) {
                return array_search($value, $constants) !== false;
            }

            return false;
        }

        /**
         * Check if a constant by the given name exists.
         * 
         * @param string $key Key to search for
         * 
         * @return bool
         */
        public static function hasKey(string $key): bool {
            if($constants = \Cls::getConstants(static::class)) {
                return array_key_exists($key, $constants);
            }

            return false;
        }

        /**
         * Get the value of a constant by a given name.
         * 
         * @param string $key Key to get the value for
         * @return array|string|bool|int|float
         */
        public static function getValue(string $key): array|string|bool|int|float|null {
            if($constants = \Cls::getConstants(static::class)) {
                if(count($constants) > 0) {
                    if($value = $constants[$key]) {
                        return $value;
                    }
                }
            }

            return null;
        }

        /**
         * Get the key of a given value.
         * 
         * @param array|string|bool|int|float $value Value to search by
         * @return string
         */
        public static function getKeyOf(array|string|bool|int|float $value): string|null {
            if($constants = \Cls::getConstants(static::class)) {
                if(count($constants) > 0) {
                    if($key = array_search($value, $constants)) {
                        return $key;
                    }
                }
            }

            return null;
        }

        /**
         * Get all of the constants as an associate array.
         * 
         * @return array|null
         */
        public static function getKeys(): array|null {
            if($constants = \Cls::getConstants(static::class)) {
                return array_keys($constants);
            }

            return null;
        }

        /**
         * Tokenise an array of key names to their value equivalents.
         * 
         * @param array $array
         * @return array
         */
        public static function tokenise(array $array): array {
            return \Arr::tokenise($array, \Arr::flip(
                \Cls::getConstants(static::class)
            ));
        }

        /**
         * Get the keys for the given values.
         * 
         * @param mixed[] $values
         * 
         * @return string[]
         */
        public static function getKeysOf(array $values): array {
            $enumValues = static::getValues();

            return \Arr::map(
                $values,
                function(int $value) use($enumValues): string {
                    return ($key = array_search($value, $enumValues)) !== false ? $key : null;
                }
            );
        }

        /**
         * Unpack an integer using this enum's values.
         * 
         * @param int $integer
         * 
         * @return array
         */
        public static function unpack(int $integer): array {
            $values = static::getValues();
            $unpacked = [];

            foreach($values as $key => $value) {
                if(!is_int($value))
                    throw new RuntimeException("Unable to check bits for value " . static::class . "::{$key} as it isn't an integer.");

                if(($integer & $value) === $value)
                    $unpacked[] = $value;
            }

            return $unpacked;
        }
    }
}

?>