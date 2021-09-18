<?php

namespace Slate\Structure {
    trait TEnum {
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
    }
}

?>