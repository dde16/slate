<?php declare(strict_types = 1);

namespace Slate\IO\Trait {
    use Slate\Exception\PathNotFoundException;
    use Slate\Exception\IOException;

    trait TStreamWriteable {
        public function writeEx(string $data, int $size = null): void {
            $this->assertOpen();

            $this->rewind();
            $this->truncate();
            $this->write($data, $size);
        }

        public function writebyte(int $data): void {
            $this->assertOpen();

            if(is_int($data)) {
                if($data > 0xFF) {
                    throw new IOException("Writing to files by integer must be an 8-bit integer.");
                }

                $data = chr($data);
            }

            $this->write($data);
        }

        public function write(string $data, int $size = null): void {
            $this->assertOpen();

            $status = fwrite($this->resource, $data, $size);

            if($status === FALSE)
                throw new IOException("Error while writing to stream.");
        }

        public function truncate(int $size = null): void {
            $this->assertOpen();

            $status = ftruncate($this->resource, $size ?? 0);

            if($status === FALSE) {
                throw new IOException("Error while truncating stream.");
            }
        }

        public function flush(): bool {
            $this->assertOpen();
            
            return fflush($this->resource);
        }
    }
}

?>