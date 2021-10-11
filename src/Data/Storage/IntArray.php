<?php

namespace Slate\Data\Storage {
    use Slate\Data\BasicArray;

    /**
     * A class aimed to handle larger sets of integers or 64 bit bytes,
     * very much like tthe UInt64Array of javascript.
     */
    class IntArray extends BasicArray {
        protected static string $container = "integers";

        public array $integers = [];
        public int   $bitsize = 0;

        public function __construct(array $integers = [], int $bitsize = 64) {
            $this->bitsize  = $bitsize;
            $this->integers = $integers;
        }

        /**
         * Xor the values of multiple arrays.
         * 
         * @param array ...$arrays
         * 
         * @return array
         */
        public function xor(): int {
            $accumulator = 0;

            foreach($this->integers as $integer) $accumulator ^= $integer;

            return $accumulator;
        }
        
        /**
         * Add an integer to the array witth overflow support.
         *
         * @param  mixed $with
         * @return void
         */
        public function add(int $with): void {
            $index        = count($this->integers)-1;
            $overflow     = true;

            if($index === -1)
                return;

            do {
                $integer      = &$this->integers[$index--];
                list($integer, $overflow) = \Integer::add($integer, $with);

                $with = 1;
            } while($overflow && $index > -1);
        }
        
        /**
         * Parse an IntArray from a hex string.
         *
         * @param  mixed $hex
         * @return void
         */
        public function fromHex(string $hex): void {
            $nibbles = $this->bitsize / 4;

            $this->integers = \Arr::map(
                \Str::split($hex, $nibbles),
                'hexdec'
            );
        }
        
        /**
         * Parse an IntArray from a binary string.
         *
         * @param  mixed $binary
         * @return void
         */
        public function fromString(string $binary): void {
            $chars = $this->bitsize / 8;

            $this->integers =
                \Arr::map(
                    \Str::split(
                        $binary,
                        $chars
                    ),
                    function($pair) {
                        $sum = 0;
                        
                        for($index = strlen($pair)-1; $index >= 0; $index--) {
                            $char = $pair[$index];

                            $sum |= ord($char) << ($index * intval($this->bitsize / 8));
                        }

                        return $sum;
                    }
                );
        }
        
        /**
         * Convert the IntArray to a hex string.
         *
         * @param  mixed $pad        The character to pad the string with.
         * @param  mixed $separator
         * @return void
         */
        public function toHex(string $pad = "0", string $separator = ""): string {
            $nibbles = $this->bitsize / 4;

            return \Arr::join(\Arr::map(
                $this->integers,
                function($int) use($nibbles, $pad) {
                    return \Str::padLeft(dechex($int), $pad, $nibbles);
                }
            ), $separator);
        }
        
        /**
         * Convert the IntArray to a binary string.
         *
         * @param  mixed $separator
         * @return void
         */
        public function toBinary(string $separator = " "): string {
            return \Arr::join(\Arr::map($this->integers, function($int) { return \Str::padLeft(decbin($int), "0", $this->bitsize); }), $separator);
        }
     
        /**
         * Convert the IntArray to a byte string of a given size.
         *
         * @param  int $charsize
         * @return void
         */
        public function toString(int $charsize = 8): string {
            if($charsize == null)
                $charsize = $this->bitsize;

            $charsizeMax = (1 << $charsize)-1;
            $intPackSize = (int)($this->bitsize / $charsize);

            $string = "";

            foreach($this->integers as $integer) {
                if($intPackSize > 1) {
                    for($index = $intPackSize-1; $index > -1; $index--) {
                        $string .=
                            chr(
                                ($integer >> ($index * $charsize)
                            ) & $charsizeMax
                        );
                    }
                }
                else {
                    $string .= chr($integer);
                }
            }

            return $string;
        }

                
        /**
         * Get the aggregate bit size of the array.
         *
         * @return int
         */
        public function getSize(): int {
            return $this->bitsize * count($this->integers);
        }
        
        /**
         * Shift all bits to the right by a given amount.
         *
         * @param  mixed $shift
         * @return void
         */
        public function shiftRight(int $shift): void {
            $mostSignificantBitShift  = $shift % $this->bitsize; // 7
            $leastSignificantBitShift = $this->bitsize - $mostSignificantBitShift; // 1

            $intShift = $shift / $this->bitsize; // 1

            for ($currentIndex = count($this->integers) - 1; $currentIndex > -1; $currentIndex--){
                if($currentIndex > $intShift-1) {
                    $mostSignificantIntShift = $currentIndex - $intShift;

                    $this->integers[$currentIndex] = $this->integers[$mostSignificantIntShift] << $mostSignificantBitShift;

                    if($currentIndex != $intShift)
                        $this->integers[$currentIndex] |= ($this->integers[$mostSignificantIntShift + 1] >> $leastSignificantBitShift);
                }
                else {
                    $this->integers[$currentIndex] = 0;
                }
            }
        }

        /**
         * Shift all bits to the left by a given amount.
         *
         * @param  mixed $shift
         * @return void
         */
        public function shiftLeft(int $shift): void {
            $mostSignificantBitShift  = $shift % $this->bitsize;
            $leastSignificantBitShift = $this->bitsize - $mostSignificantBitShift;

            $intShift = $shift / $this->bitsize;
            $lastIndex = count($this->integers) - $intShift;

            for ($currentIndex = 0; $currentIndex < count($this->integers); $currentIndex++){
                if ($currentIndex <= $lastIndex) {
                    $mostSignificantIntShift = $currentIndex + $intShift;

                    $this->integers[$currentIndex] = $this->integers[$mostSignificantIntShift] << $mostSignificantBitShift;

                    if($currentIndex != $lastIndex)
                        $this->integers[$currentIndex] |= ($this->integers[$mostSignificantIntShift + 1] >> $leastSignificantBitShift);
                }
                else {
                    $this->integers[$currentIndex] = 0;
                }
            }
        }
        
        /**
         * Shift the array by a given amount and direction.
         *
         * @param  mixed $shift If negative, will go left, positive; right.
         * @return void
         */
        public function shift(int $shift): void {
            $this->{($shift > 0) ? 'shiftRight' : 'shiftLeft'}($shift);
        }
        
        /**
         * Flip the bits of all integers in the array.
         *
         * @return void
         */
        public function flip(): void {
            foreach($this->integers as &$int) $int = ~$int;
        }
        
        /**
         * Parse an IntArray from a UID string.
         *
         * @param  mixed $uid
         * @return static
         */
        public static function fromUID(string $uid): static {
            $uid = \Str::replace($uid, "-", "");

            if(($length = strlen($uid)) !== 32)
                throw new \Error("Invalid UID length of {$length}.");

            return(new static(\Arr::map(\Str::split($uid, 16), 'hexdec')));
        }

        /**
         * Convert an IntArray to a UID string.
         *
         * @return string
         */
        public function toUID(): string {
            if(count($this->integers) !== 2)
                throw new \Error("There must be atleast two integers to make a valid guid.");

            $hex = \Str::split($this->toHex(), 4);

            //8-4-4-4-12
            return \Arr::join([
                \Arr::join(array_slice($hex, 0, 2)),
                \Arr::join(array_slice($hex, 2, 1)),
                \Arr::join(array_slice($hex, 3, 1)),
                \Arr::join(array_slice($hex, 4, 1)),
                \Arr::join(array_slice($hex, 5, 3))
            ], "-");
        }
    }
}

?>