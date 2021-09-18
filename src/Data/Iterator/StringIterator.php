<?php

namespace Slate\Data\Iterator {
    use ArrayAccess;
    use Slate\Data\Iterator\IAnchoredIterator;
    use Slate\Data\Iterator\IExtendedIterator;
    use Slate\Data\Iterator\IMatchingIterator;
    use Slate\Data\TOffsetExtended;
    use Slate\IO\IStreamReadable;
    use Slate\IO\IStreamWriteable;

    /**
     * A file-like iterator for strings.
     * Useful for testing IO components without touching the filesystem.
     */
    final class StringIterator implements IStreamReadable, IExtendedIterator, IMatchingIterator, IAnchoredIterator, ArrayAccess {
        use TOffsetExtended;
        use TMatchingIterator;
        use TAnchoredIterator;

        protected string $string;
        protected int    $pointer;

        public function __construct(string &$string) {
            $this->string  = $string;
            $this->seek(0);
        }

        public function distance(): int {
            $lastAnchor = $this->anchor-1;

            return  $this->tell() - (($lastAnchor >= 0) ? $this->anchors[$lastAnchor] : 0);
        }

        public function anchor(): void {
            $this->anchors[++$this->anchor] = $this->pointer;
        }

        public function revert(): void {
            if($this->anchor < 0)
                throw new \Error("Trying to revert a non-existent anchor.");

            $this->pointer = $this->anchors[$this->anchor--];
        }
        
        /**
         * @see ftell
         *
         * @return int
         */
        public function tell(): int {
            return $this->pointer;
        }

        public function offsetAssign(string|int $offset, string $value): void {
            throw new \Error("Assigning strings to offsets is not supported due to its inefficient process.");
        }

        public function offsetPush(string $value): void {
            if(!\Str::isChar($value))
                throw new \Error("Strings pushed must be single characters.");

            $this->string .= $value;
        }

        public function offsetExists($offset): bool {
            return $offset > strlen($this->string);
        }

        public function offsetUnset($offset): void {            
            throw new \Error("Unsetting string offsets is not supported due to its inefficient process.");
        }

        public function offsetGet($offset): mixed {
            return substr($this->string, $offset, 1);
        }


        public function read(int $length = null, bool $eofNull = false): string|null {
            if($this->isEof()) 
                return null;

            $next = $this->tell()+$length;
            $data = substr($this->string, $this->tell(), $length);

            $this->seek((!$this->valid($next)) ? (strlen($this->string)) : $next);

            return $data;
        }

        

        public function readUntil(string $until, bool $eof = true): ?string {
            $buffer = "";
            $this->anchor();
            $untilLength = strlen($until);
            $seekby = $untilLength === 1 ? 0 : -1;
            // $seekby = ($untilLength * -1) + 1;
            $found = false;

            while(($frame = $this->read($untilLength)) !== null && !$found) {
                if(!($found = ($frame == $until))) {
                    $buffer .= substr($frame, 0, 1);
                }
                
                if(($this->isEof())) {
                    $found = $eof;
                }
                else $this->relseek($seekby);
            }


            if($found) return $buffer;

            $this->revert();
        }
        
        public function pipe(IStreamWriteable $stream, int $bufferSize = 8096): void {
            $this->anchor();
            $this->rewind();

            while(!$this->isEof()) {
                $stream->write($this->read($bufferSize));
                $stream->flush();
            }

            $this->revert();
        }

        public function readChar(): ?string {
            return $this->read(1);
        }

        public function readByte(): ?int {
            return (($char = $this->read(1)) !== null) ? ord($char) : null;
        }

        public function readAll(): string|null {
            return $this->string;
        }

        /**
         * @see rewind
         */
        public function rewind(): void {
            $this->pointer = 0;
        }
        
        /**
         * @see current
         */
        public function current(): mixed {
            return $this->offsetGet($this->pointer);
        }

        /**
         * @see prev
         */
        public function prev(): void {
            $this->pointer--;
        }

        /**
         * @see key
         */
        public function key(): int|string|null {
            return $this->pointer;
        }

        /**
         * @see next
         */
        public function next(): mixed {
            return ++$this->pointer;
        }
        
        public function valid(): bool {
            return !$this->isEof();
        }

        public function isEof(int|bool $pointer = true): bool {
            return ((is_bool($pointer) && $pointer ? $this->pointer : $pointer) >= strlen($this->string));
        }

        public function relseek(int $pointer): void {
            $this->seek($this->tell() + $pointer);
        }

        public function seek(int $pointer): void {
            if($pointer < 0)
                throw new \Error("The pointer must be a positive integer.");

            $this->pointer = $pointer;
        }
    }
}

?>