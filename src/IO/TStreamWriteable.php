<?php

namespace Slate\IO {
    use Slate\Exception\PathNotFoundException;
    use Slate\Exception\IOException;

    trait TStreamWriteable {
        public function writebyte(int $data): void {
            $this->assertOpen();

            if(\Any::isInt($data)) {
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

            if($size === NULL)
                $size = $this->getSize();

            $status = ftruncate($this->resource, $size);

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