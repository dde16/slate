<?php

class Integer extends ScalarType implements \Slate\Data\ISizeStaticallyAttainable  {
    const NAMES            = ["int", "integer"];
    const GROUP            = ScalarType::class;
    const VALIDATOR        = "is_int";
    const CONVERTER        = "intval";

    const MIN              = PHP_INT_MIN;
    const MAX              = PHP_INT_MAX;

    const BYTESIZE         = PHP_INT_SIZE;
    const BITSIZE          = PHP_INT_SIZE*8;

    public const OR  = (1<<0);
    public const XOR = (1<<1);
    public const AND = (1<<2);

    public static function fromBinary(string $binary) {
        $bytes = Integer::BYTESIZE;

        $binary = mb_convert_encoding($binary, "utf-8");
        $length = strlen($binary);

        if($length > $bytes)
            throw new \Error("Binary string must be of length {$bytes}.");

        $binary = \Str::padRight($binary, "\0", $bytes);

        for($sum = $index = 0; $index < $length; $sum |= ord($binary[$index]) << ($index++ * Integer::BYTESIZE));

        return $sum;
    }

    /**
     * Parse a string into an integer by a given radix.
     * 
     * @param string        $string
     * @param char[]|string $radix
     * 
     * @return int
     */
    public static function fromBase(string $string, array|string $radix): int {
        if(is_string($radix))
            $radix = \Str::split($radix);

        $string = \Str::reverse($string);
        $base = count($radix);

        return array_sum(
            \Arr::mapAssoc(
                \Str::split($string),
                function($index, $char) use($radix, $base) {
                    $ord = \Arr::find($radix, $char);

                    if($ord === -1)
                        throw new Slate\Exception\ParseException("Invalid character '{$char}' at position {$index}");

                    return [$index, ($base**$index) * $ord];
                }
            )
        );
    }

    /**
     * Convert an integer to a given radix.
     * 
     * @param int           $integer
     * @param char[]|string $radix
     */
    public static function toBase(int $integer, array|string $radix): string {
        if(is_string($radix))
            $radix = \Str::split($radix);

        $base  = count($radix);
        $stack = "";

        do {
            list($integer, $remainder) = \Math::divmod($integer, $base);

            $stack .= $radix[$remainder];
        } while($integer !== 0);

        return \Str::reverse($stack);
    }

    /**
     * Add two integers by a given bitsize with overflow detection.
     * 
     * @param int $a
     * @param int $b
     * @param int $bitsize
     * 
     * @return array
     */
    public static function add(int $a, int $b, int $bitsize = self::BITSIZE): array {
        $mask           = \Integer::fillBy(0, $bitsize-1);
        $carry          = ($a & $b) & $mask;
        $result         = ($a ^ $b) & $mask;
        $overflow       = false;
        
        while($carry !== 0) {
            if($carry & (1 << ($bitsize - 1)))
                $overflow = true;
        
            $shiftedcarry = $carry << 1;
            $carry        = $result & $shiftedcarry;
            $result       = $result ^ $shiftedcarry;
        }

        return [$result & $mask, $overflow];
    }
    
    public static function getSize():int {
        return \Integer::BITSIZE;
    }

    public static function fromDateTime(\DateTimeInterface|\DateInterval|int $datetime): int {
        if(is_object($datetime)) {
            if(\Cls::hasInterface($datetime, \DateTimeInterface::class)) {
                return $datetime->getTimestamp();
            }
            else if(\Cls::isSubclassInstanceOf($datetime, \DateInterval::class)) {
                return 
                    ($datetime->s)
                    + ($datetime->i * 60)
                    + ($datetime->h * 60 * 60)
                    + ($datetime->d * 60 * 60 * 24)
                    + ($datetime->m * 60 * 60 * 24 * 30)
                    + ($datetime->y * 60 * 60 * 24 * 365);
            }
        }

        if($datetime < 0)
            throw new \Error("Timestamp must be a positive integer.");

        return $datetime;
    }

    public static function fromDateTimeSpan(\DateTimeInterface|\DateInterval|int $datetime): int {
        if(is_object($datetime)) {
            if(\Cls::isSubclassInstanceOf($datetime, \DateInterval::class)) {
                $datetime = 
                    ($datetime->s)
                    + ($datetime->i * 60)
                    + ($datetime->h * 60 * 60)
                    + ($datetime->d * 60 * 60 * 24)
                    + ($datetime->m * 60 * 60 * 24 * 30)
                    + ($datetime->y * 60 * 60 * 24 * 365);
            }
            else if(\Cls::hasInterfaces($datetime, \DateTimeInterface::class)) {
                return $datetime->getTimestamp();
            }
        }

        if($datetime < 0)
            throw new \Error("Timestamp must be a positive integer.");

        return time() + $datetime;
    }

    /**
     * Generate an integer with its bits filled in a given range.
     * 
     * @param int $from Starting at zero, what bit to start from (from the right)
     * @param int $to   Starting at zero, what bit to end at (from the right)
     * 
     * @return int
     */
    public static function fillAt(int $from, int $to): int {
        /**
         * Works by taking advantage of how bits are assigned when bit shifted, depending on the
         * sign. Eg. 1 right shift when negative, 0 when positive.
         * 
         * Eg.
         * From : 7
         * To   : 5
         * 
         * 1) 00000001 (1)
         * 2) 00001000 (1 << ((7 - 5) + 1 = 3))
         * 3) 10001000 (change sign)
         * 4) 01110111 (not)
         * 5) 11100000 (<<7)
        */
        return ~(-(1 << ($to - $from + 1))) << $from;
    }

    /**
     * Generate a bitmask to only the integers in a given range.
     * 
     * @param int $integer 
     * @param int $from
     * @param int $to
     * 
     * @return int
     */
    public static function only(int $integer, int $from, int $to): int {
        return $integer & \Integer::fillAt($from, $to);
    }

    /**
     * Generate a bitmask to only the integers outside of a given range.
     * 
     * @param int $integer
     * @param int $from
     * @param int $to
     * 
     * @param int
     */
    public static function except(int $integer, int $from, int $to): int {
        return $integer & ~\Integer::fillAt($from, $to);
    }

    /**
     * Generate an integer where bits are 1 from an offset to a given length.
     * 
     * @param int $offset
     * @param int $by
     * 
     * @return int
     */
    public static function fillBy(int $offset, int $by): int {
        return \Integer::fillAt($offset, $offset+$by);
    }

    /**
     * Perform integer overflow.
     * 
     * @param int|float $integer
     * @param int $bitsize
     * 
     * @return int
     */
    public static function overflow(int|float $integer, int $bitsize): int {
        $totalmax = 1 << $bitsize;
        $submax   = 1 << ($bitsize - 1);
    
        $integer = $integer % $totalmax;
    
        if($integer > ($submax-1)) {
            $integer -= $totalmax;
        }
        else if($integer < (-$submax)) {
            $integer += $totalmax;
        }

        return $integer;
    }

    /**
     * And integer operator with more utils.
     * 
     * @param int       $bitpack
     * @param int|array $orpack
     * 
     * @return int
     */
    public static function hasBits(int $bitpack, int|array $orpack): bool {
        $orpack = \Any::isArray($orpack) ? \Arr::or($orpack) : $orpack;

        return ($bitpack !== 0 && $orpack === 0) ?: (($bitpack & $orpack) === $orpack);
    }

    /**
     * Convert a permission string to its integer value.
     * Eg. 0666
     * 
     * @param array|string $permissions
     * 
     * @return int
     */
    public static function permissions(array|string $permissions): int {
        if(is_string($permissions)) {
            $permissions = \Arr::map(\Str::split($permissions), 'intval');
        }

        $bits = 0;
        $count = count($permissions)-1;

        for($index = $count; $index >= 0; $index--) {
            $bits ^= $permissions[$index] << (($count-$index) * 3);
        }

        return $bits;
    }

    /**
     * Get the maximum number for a given bitsize.
     */
    public static function max(int $bitsize): int {
        return ((1 << $bitsize)-1);
    }

    public static function parse($value): int|null {
        return !is_int($value) ? filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) : $value;
    }

    /**
     * Get the size in bytes for a given integer.
     * 
     * @param int $int
     * 
     * @return int
     */
    public static function sizeof(int $int): int {
        return (int)ceil(static::bitsize($int, precise: false) / 8);
    }

    /**
     * Get the bitsize for a given integer.
     * 
     * @param int $int
     * @param bool $precise Give exact bitsize or round up to the nearest 8
     */
    public static function bitsize(int $int, bool $precise = true): int {
        if($int > 0) $int += 1;
        $bitsize = (log((float)$int, 2));

        return (int)(\Math::jump($bitsize, $precise ? 1 : 8));
    }

    /**
     * Reverse a given integer's bits.
     * 
     * @param int $integer
     * 
     * @return int
     */
    public static function reverse(int $bits): int {
        $bitsize = \Integer::bitsize($bits);
        $tmp     = 0;

        for($shift = 0; $shift <= $bitsize; $shift++) {
            $tmp |= $bits & 1; // putting the set bits of num
            $bits >>= 1; //shift the tmp Right side 
            $tmp <<= 1;  //shift the tmp left side 
        }
            
            
        return $tmp;
    }
}

?>
