<?php declare(strict_types = 1);

namespace Slate\Data\Iterator {
    use ArrayAccess;
    use Slate\Data\Iterator\IAnchoredIterator;
    use Slate\Data\Iterator\IExtendedIterator;
    use Slate\Data\Iterator\IMatchingIterator;
    use Slate\Data\TOffsetExtended;
    use Slate\IO\Contract\IStreamReadable;
    use Slate\IO\Contract\IStreamWriteable;

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
        
        /**
         * @see ftell
         *
         * @return int
         */
        public function tell(): int {
            return $this->pointer;
        }

        public function offsetAssign(string|int $offset, string $value): void {
            if(!\Str::isChar($value))
                throw new \Error("Strings offset assigned must be single characters.");

            $this->string[$offset] = $value;
        }

        public function offsetPush(string $value): void {
            if(!\Str::isChar($value))
                throw new \Error("Strings pushed must be single characters.");

            $this->string .= $value;
        }

        public function offsetExists($offset): bool {
            return $offset < strlen($this->string);
        }

        public function offsetUnset($offset): void {            
            throw new \Error("Unsetting string offsets not supported.");
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
        public function next(): void {
            ++$this->pointer;
        }
        
        public function valid(): bool {
            return !$this->isEof();
        }

        public function isEof(int|bool $pointer = true): bool {
            return (((is_bool($pointer) && $pointer) ? $this->pointer : $pointer) >= strlen($this->string));
        }

        public function relseek(int $pointer): bool {
            return $this->seek($this->tell() + $pointer);
        }

        public function seek(int $pointer): bool {
            if($pointer < 0)
                return false;

            $this->pointer = $pointer;

            return true;
        }
    }
}

?>