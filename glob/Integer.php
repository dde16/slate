<?php

use Slate\Data\Iterator\ArrayExtendedIterator;

/**
 * A class to double as a central point for Integer functions while being 
 * a way to class-ify types.
 * 
 * TODO: in the add with overflow detection - use & 1 << 63 to detect overflow
 * TODO: remove fromBase and toBase when the IntArray can perform these tasks
 */
class Integer extends ScalarType implements \Slate\Data\ISizeStaticallyAttainable  {
    public const NAMES            = ["int", "integer"];
    public const GROUP            = ScalarType::class;
    public const VALIDATOR        = "is_int";
    public const CONVERTER        = "intval";

    public const MIN              = PHP_INT_MIN;
    public const MAX              = PHP_INT_MAX;

    public const BYTESIZE         = PHP_INT_SIZE;
    public const BITSIZE          = PHP_INT_SIZE*8;

    public const SI  = 1000;
    public const IEC = 1024;

    public const BYTE     = 1;

    //SI
    public const KILOBYTE = 2;
    public const MEGABYTE = 3;
    public const GIGABYTE = 4;
    public const TERABYTE = 5;
    public const PETABYTE = 6;

    //IEC
    public const KIBIBYTE = 2;
    public const MEBIBYTE = 3;
    public const GIBIBYTE = 4;
    public const TIBIBYTE = 5;
    public const PIBIBYTE = 6;

    /**
     * Memory units in the SI format.
     * 
     * @var array<string,string[]>
     */
    public const SI_UNITS = [
        self::BYTE     => ["Byte",     "B"],
        self::KILOBYTE => ["Kilobyte", "KB"],
        self::MEGABYTE => ["Megabyte", "MB"],
        self::GIGABYTE => ["Gigabyte", "GB"],
        self::TERABYTE => ["Terabyte", "TB"],
        self::PETABYTE => ["Petabyte", "PB"]
    ];

    /**
     * Memory units in the IEC format.
     * 
     * @var array<string,string[]>
     */
    public const IEC_UNITS = [
        self::BYTE     => ["Byte",     "B"],
        self::KILOBYTE => ["Kibibyte", "KiB"],
        self::MEGABYTE => ["Mebibyte", "MiB"],
        self::GIGABYTE => ["Gibibyte", "GiB"],
        self::TERABYTE => ["Tibibyte", "TiB"],
        self::PETABYTE => ["Pibibyte", "PiB"]
    ];

    public const OR  = (1<<0);
    public const XOR = (1<<1);
    public const AND = (1<<2);

    /**
     * Determine the most efficient storage method for a given integer.
     *
     * @param integer $integer
     *
     * @return array<string|int,string,int>
     */
    public static function efficientStorageOf(int $integer): array {
        $digits  = \Math::digits($integer);
        $bitsize = \Integer::bitsize($digits, false);

        return (
            $bitsize > $digits
                ? ["string", $digits]
                : ["int", $bitsize]
        );
    }

    public static function getReducedSize(int $bytes, int $standard = self::SI) {
        $value = $bytes;
        $units = new ArrayExtendedIterator(match($standard) {
            self::SI => self::SI_UNITS,
            self::IEC => self::IEC_UNITS
        });
        $unit = $units->current();
        $units->next();

        while($value >= $standard && $units->valid()) {
            $unit   = $units->current();
            $value /= $standard;
            $units->next();
        }
        
        return [$value, $unit];
    }

    public static function fromBinary(string $binary) {
        $bitsize = strlen($binary);
        $integer = 0;

        for($offset = 0; $offset < $bitsize; $offset++) {
            $integer |= intval($binary[$offset]) << ($bitsize - $offset - 1);
        }

        return $integer;
    }

    public static function toBinary(int $integer, int $bitsize = self::BITSIZE): string {
        return \Str::padLeft(decbin($integer), "0", $bitsize);
    }

    public static function fromBytes(string $bytes): int {
        $bytesize = Integer::BYTESIZE;
        $bytes    = \Str::padLeft($bytes, "\0", $bytesize);

        if($bytesize > strlen($bytes))
            throw new \Error("Binary string must be of length {$bytesize}.");

        $sum = 0;

        for($index = strlen($bytes)-1; $index > -1; $index--) {
            $sum |= ord($bytes[$index]) << (($bytesize - $index - 1) * 8);
        }

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
     * Generate an integer with its bits filled in a given range (reads right to left).
     * 
     * @param int $from Starting at zero, what bit to start from (from the right)
     * @param int $to   Starting at zero, what bit to end at (from the right)
     * 
     * @return int
     */
    public static function fillAt(int $from, int $to): int {
        /**
         * Eg.
         * From : 7
         * To   : 5
         * Size : 8
         * 
         * 1) 00000001 (1)
         * 2) 00001000 (1 << ((7 - 5) + 1 = 3))
         * 3) 10001000 (change sign always negative)
         * 4) 01110111 (not)
         * 5) 11100000 (<<7)
        */
        return ~(-(1 << ($to - $from + 1))) << $from;
    }

    /**
     * Generate a bitmask to only the integers in a given range (reads from right to left).
     * 
     * @param int $integer 
     * @param int $from
     * @param int $to
     * 
     * @return int
     */
    public static function only(int $integer, int $from, int $to, bool $zero = false): int {

        return ($zero
            ? ($integer >> $from) & \Integer::fillBy(0, $to - $from - 1)
            : ($integer & \Integer::fillAt($from, $to))
        );
    }

    /**
     * Split an integer by a given size.
     */
    public static function split(int $integer, int $bitsize, int $offset, int $splitsize, int $direction = -1): array {
        $in = (int)ceil($bitsize / $splitsize);
        $ints = [];
        $carry = null;
    
        for($i = 0; $i < $in; $i++) {
            $from = ( $offset + ( $i    * $splitsize));
            $to   = (($offset + (($i+1) * $splitsize)));
    
            $_from = ($direction === 1 ? ($bitsize - $to)   : $from+1);
            $_to   = ($direction === 1 ? ($bitsize - $from) : $to);

            $carried = false;

            $from = $_from;
            $to = $_to;
    
            if($from < 0) {
                $from = 0;
                $carried = true;
            }
    
            if($to < 0) {
                $to = 0;
                $carried = true;
            }
            
            $subint = \Integer::only($integer, $from, $to, true);

            if($carried) {
                $carry = $subint;
            }
            else {
                $ints[] = $subint;
            }
        }
    
        return [$ints, $carry];
    }

    /**
     * Generate a bitmask to only the integers outside of a given range (reads right to left).
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
     * And integer operator with more utils and strict matching.
     * Eg. 6 & ((1 | 2) = 3) = 2 which evaluates to boolean true.
     * Thus will not match all bits.
     * 
     * @param int       $bitpack
     * @param int|array $orpack
     * 
     * @return int
     */
    public static function hasBits(int $bitpack, int|array $orpack): bool {
        $orpack = is_array($orpack) ? \Arr::or($orpack) : $orpack;

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
            $permissions = \Arr::map(\Str::split($permissions), Closure::fromCallable('intval'));
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
