<?php

namespace Slate\Data\Storage {

    use App\Auxiliary\ArrayAssociativeIterator;
    use Generator;
    use Slate\Data\BasicArray;
    use Slate\Data\Iterator\ArrayExtendedIterator;

    /**
     * A class aimed to handle larger sets of integers, very much
     * like the UInt64Array of javascript with more functionality.
     * 
     * TODO: every n bits
     * TODO: base conversions using every n bits
     * TODO: incrementing/decrementing
     */
    class IntArray extends BasicArray {
        protected static string $container = "integers";

        /**
         * Integer array.
         * In the context of a stream: the first element would be the oldest piece of data and the last element the newest.
         *
         * @var int[]
         */
        public array $integers = [];
        public int   $bitsize = 0;

        public function __construct(array $integers = [], int $bitsize = 64) {
            if(log($bitsize, 2) % 1 != 0)
                throw new \Error("Bitsize must be a power of two.");

            $this->bitsize  = $bitsize;
            $this->integers = $integers;
        }

        /**
         * Get n bits without overlap.
         *
         * @return Generator
         */
        public function chunk(int $bits): Generator {
            $ints       = new ArrayExtendedIterator($this->integers);

            /**
             * The carry for the next integer if it overflows.
             * 
             * @var int $mod
             */
            $subCarry   = 0;

            /**
             * A position modifier that tells how many bits we need to retrieve from the last integer.
             * 
             * @var int $offset
             */
            $offset        = 0;

            while($ints->valid()) {
                $currentInt   = $ints->current();

                $subCarry ??= 0;

                if($offset === 0)
                    $offset = $bits;
                
                /** If there is an overlap between integers */
                if($offset > 0) {
                    $from   = $this->bitsize - $offset;
                    $to     = $this->bitsize;
                    $subInt = \Integer::only($currentInt, $from, $to, true);

                    $leftOffset = $bits - $offset;

                    if($leftOffset > 0) {
                        /** Yield the complete integer */
                        yield $subInt | ($subCarry << $offset);
                    }
                }

                list($subInts, $subCarry)
                    = \Integer::split(
                        $currentInt,
                        $this->bitsize,
                        ($bits - $offset),
                        $bits,
                        1
                    );

                foreach($subInts as $subInt)
                    yield $subInt;
                
                $offset = (($this->bitsize - ($bits - $offset)) % $bits);

                $ints->next();
            }

            /** If there is a remaining sub carry that was cut off by the end of the integer array */
            if($subCarry !== null)  
                yield $subCarry << ($bits - $offset);
        }

        /**
         * Trim the end of the array so it doesn't contain empty numbers.
         */
        public function trim(): void {
            $iterator = new ArrayAssociativeIterator($this->integers);

            while($iterator->valid())
                if($iterator->current() === 0)
                    unset($this->integers[$iterator->key()]);
        }

        public function toBase2(string $base) {
            $basin = strlen($base);
            $check = 1 << ($basin-1);
            $shift = 0;

            while($shift < $this->getSize()) {

                // if()

            }
        }

        public function toBase(string $base): string {
            $baseSize = strlen($base);
            $basePow = log($baseSize, 2);

            if(($basePow % 1) != 0)
                throw new \Error("The base size mustt be a power of two.");

            $basePow = (int)$basePow;

            return \Arr::join(\Arr::map(
                iterator_to_array($this->chunk($basePow)),
                fn(int $int): string => $base[$int]
            ));
        }

        /**
         * Xor the values of multiple arrays.
         * 
         * @param array ...$arrays
         * 
         * @return array
         */
        public function xor(): int {
            for($accumulator = $index = 0; $index < count($this->integers); $accumulator ^= $this->integers[$index++]);

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
            return \Arr::join(
                \Arr::map(
                    $this->integers,
                    fn(int $int): string => \Str::padLeft(decbin($int), "0", $this->bitsize)
                ),
                $separator
            );
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
                        $string .= chr(($integer >> ($index * $charsize)) & $charsizeMax);
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
        public function shiftRight(int $shift = 1): void {
            /**
             * Works by determining how many integers will be shifted and then how many of the last
             * integer needs to be shifted.
             */

            /**
             * Get the remaining bitshift from the left.
             * 
             * @var int $leastSignificantBitShift
             */
            $mostSignificantBitShift  = $shift % $this->bitsize;

            /**
             * Get the remaining bitshift from the right.
             * 
             * @var int $leastSignificantBitShift
             */
            $leastSignificantBitShift = $this->bitsize - $mostSignificantBitShift;

            /**
             * Get the amount of integers we need to shift by before shifting the remaining.
             * 
             * @var int $intShift
             */
            $intShift = $shift / $this->bitsize;

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
        public function shiftLeft(int $shift = 1): void {
            $mostSignificantBitShift  = $shift % $this->bitsize;
            $leastSignificantBitShift = $this->bitsize - $mostSignificantBitShift;

            $intShift = $shift / $this->bitsize;
            $lastIndex = count($this->integers) - $intShift;

            $size = count($this->integers);

            for ($currentIndex = 0; $currentIndex < $size; $currentIndex++){
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
            ($shift > 0)
                ? $this->shiftRight($shift)
                : $this->shiftLeft($shift*-1);
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