<?php declare(strict_types = 1);

namespace Slate\IO\Trait {
    use Slate\Crypto\Hash;
    use Slate\Data\Iterator\TAnchoredIterator;
    use Slate\Data\Iterator\TMatchingIterator;
    use Slate\IO\StreamEofMethod;
    use Slate\Exception\ParseException;
    use Slate\IO\Contract\IStreamWriteable;
    use Slate\IO\Stream;

    trait TStreamReadable {
        use TStreamIterable;
        use TMatchingIterator;
        use TAnchoredIterator;

        public StreamEofMethod $eofMethod = StreamEofMethod::FEOF;

        /**
         * Hash this stream with a given algorithm and buffer size.
         *
         * @param string $algorithm
         * @param integer $bufferSize The default buffer size is to match that of the sha512sum linux binaries.
         *
         * @return Hash
         */
        public function hash(string $algorithm, int $bufferSize = 32768): Hash { 
            $this->assertOpen();
            
            $hash = new Hash($algorithm);

            do {
                if(($data = $this->read($bufferSize)) !== null)
                    $hash->update($data);
                
            } while(!$this->isEof());

            return $hash;
        }

        public function json(bool $assert = false, bool $assoc = true, int $flags = 0): ?array {
            $this->assertOpen();
            $this->rewind();

            $json = json_decode($this->readAll(), $assoc, flags: $flags);

            if($assert ? json_last_error() !== JSON_ERROR_NONE : false)
                throw new ParseException(json_last_error_msg() . " while parsing json for this stream.");

            return $json;
        }

        public function anchor(): void {
            $this->assertOpen();

            $this->anchors[++$this->anchor] = $this->tell();
        }

        public function revert(): void {
            $this->assertOpen();

            if($this->anchor < 0)
                throw new \Error("Trying to revert a non-existent anchor.");

            $this->seek($this->anchors[$this->anchor--]);
        }

        public function isEof(): bool {
            $this->assertOpen();

            return match($this->eofMethod) {
                StreamEofMethod::FEOF => feof($this->resource),
                StreamEofMethod::UNREAD_BYTES => stream_get_meta_data($this->resource)['unread_bytes'] <= 0,
                StreamEofMethod::POINTER => $this->tell() >= $this->getSize(),
            };
        }

        public function readAll(int $bufferSize = Stream::BUFFER_SIZE, bool $eofNull = false): string|null {
            $this->assertOpen();

            if($eofNull ? $this->isEof() : false)
                return null;

            $data = null;
            $currentPosition = ftell($this->resource);

            while(!$this->isEof($this->resource)) {
                $data .= fread($this->resource, $bufferSize);
            }

            if($data === null)
                $this->seek($currentPosition);

            return $data;
        }

        public function pipe(IStreamWriteable $stream, int $bufferSize = 8096): void {
            $this->assertOpen();

            $this->anchor();
            $this->rewind();

            while(!$this->isEof()) {
                $stream->write($this->read($bufferSize));
                $stream->flush();
            }

            $this->revert();
        }

        public function read(int $length = null, bool $eofNull = false): string|null {  
            $this->assertOpen();

            if($length === null) {
                $data = $this->readAll(eofNull: $eofNull);
            }
            else {
                $data = fread($this->resource, $length);

                if($eofNull && $data !== false) {
                    if(strlen($data) !== $length)
                        $data = null;
                }
                else if($data === false) {
                    $data = null;
                }
            }

            return $data;
        }

        public function readChar(): string|null {
            $this->assertOpen();

            return fgetc($this->resource);
        }

        public function readByte(): int {
            $this->assertOpen();

            return ord($this->readChar());
        }

        public function setEofMethod(StreamEofMethod $eofMethod): void {
            $this->eofMethod = $eofMethod;
        }

        public function readUntil(string $until, bool $eof = true): string|null {
            $this->assertOpen();

            $buffer = "";
            $untilLength = strlen($until);
            $found = false;

            while((!$found && !$this->isEof()) ? ($frame = $this->read($untilLength)) !== null : false) {
                if(!($found = ($frame == $until)))
                    $buffer .= substr($frame, 0, 1);
                
                $this->relseek(-(strlen($frame) - 1));
            }

            return ($found || ($eof ? $this->isEof() : false)) ? $buffer : null;
        }
    }
}

?>