<?php

namespace Slate\Data\Storage {

    use Slate\Utility\TUninstantiable;

    /**
     * A facade for bitpacking.
     */
    final class Bitpack {
        use TUninstantiable;

        /**
         * Get the slice size of a given bitpack.
         * 
         * @param array $bitpack
         * 
         * @return int
         */
        public static function capacity(array $bitpack): int {
            return !is_int($bitpack[1]) ? ord($bitpack[1]) : $bitpack[1];
        }
    
        /**
         * Calcuate the slice (bit) size of a given bitpack.
         *
         * @param array $bitpack
         * 
         * @return int
         */
        public static function bitsize(array $bitpack): int {
            return intval(\Integer::BITSIZE / Bitpack::capacity($bitpack));
        }
    
        /**
         * Get an element within a bitpack.
         * 
         * @param array $bitpack
         * @param int   $index
         * @param bool  $signed
         * 
         * @return int
         */
        public static function get(array $bitpack, int $index, bool $signed = false): int {
            $capacity = Bitpack::capacity($bitpack);
            $bitsize  = intval(\Integer::BITSIZE / $capacity);
    
            if($index > ($capacity - 1))
                throw new \Error("Index is out of range.");
            
    
            $offsetLow  = ($index  * $bitsize);
            $offsetHigh = ($offsetLow + $bitsize) - 1;
        
            if(!$signed) $integer &= 1<<$bitsize; 
    
            return \Integer::only($bitpack[0], $offsetLow, $offsetHigh);
        }
    
        /**
         * A quick access for bitpacking booleans.
         * 
         * @param array $bitpack
         * @param int   $index
         * 
         * @return bool
         */
        public static function bool(array $bitpack, int $index): bool {
            return boolval(Bitpack::get($bitpack, $index, signed: false));
        }
    
        /**
         * Set an element within a bitpack.
         * 
         * @param array &$bitpack
         * @param int   $index
         * @param int   $value
         * 
         * @return void
         */
        public static function set(array &$bitpack, int $index, int $value): void {
            $bitsize  = Bitpack::bitsize($bitpack);
    
            $offsetLow  = ($index  * $bitsize);
            $offsetHigh = ($offsetLow + $bitsize) - 1;
    
            $bitpack[0] = \Integer::except($bitpack[0], $offsetLow, $offsetHigh) | \Integer::only($value, 0, $bitsize) << $index * $bitsize;
        }
    
        /**
         * Create a bitpack of a given slice size.
         * 
         * @param array $integers
         * @param int   $bitsize
         * 
         * @return array
         */
        public static function create(array $integers, int $bitsize = null): array {
            if($bitsize === null)
                $bitsize = intval(\Integer::BITSIZE / count($integers));
    
            if($bitsize < 1 || $bitsize > \Integer::BITSIZE)
                throw new \Error("Bitpack slice size must be a non-zero positive integer within system architecture limits.");
    
            $capacity = \Integer::BITSIZE / $bitsize;
    
            if(fmod($capacity, 1.0) !== 0.0)
                throw new \Error(\Str::format(
                    "Bitpack slice size must divide into {}",
                    \Integer::BITSIZE
                ));
    
            $capacity = intval($capacity);
    
            $max = 1 << $bitsize;
            $bitpack = [0, chr($capacity)];
    
            foreach($integers as $index => $integer) {
                if($integer > $max || $integer < -$max) {
                    throw new \Error("Integer at index $index falls outside the maximum for this slice size.");
                }
    
                Bitpack::set($bitpack, $index, $integer);
            }
            
            return $bitpack;
        }
    
        /**
         * Unpack a bitpack.
         * 
         * @param array $bitpack
         * 
         * @return array
         */
        public static function unpack(array $bitpack): array {
            $capacity = Bitpack::capacity($bitpack);
            $items    = [];
    
            for($index = 0; $index < $capacity; $items[] = Bitpack::get($bitpack, $index++));
    
            return $items;
        }
    }
}

?>