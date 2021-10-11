<?php

namespace Slate\IO {
    use Slate\Exception\PathNotFoundException;
    use Slate\Exception\IOException;

    class StreamBase implements IStreamBase {
        const BUFFER_SIZE = 8192;

        protected $resource;

        public function __construct($resource) {
            $this->resource = $resource;
        }

        public function __destruct() {
            $this->close();
        }

        public  function getResource(): mixed {
            return $this->resource;
        }

        public function getStatistics(): array {
            $this->assertOpen();

            return fstat($this->resource);
        }

        public function getSize(): int {
            return $this->getStatistics()["size"];
        }

        public function isOpen(): bool {
            return $this->resource !== NULL;
        }

        public function tell(): int|false {
            $this->assertOpen();

            return ftell($this->resource);
        }
        
        public function rewind(): void {
            $this->assertOpen();

            rewind($this->resource);
        }
        
        public function seek(int $position): bool {
            $this->assertOpen();

            return fseek($this->resource, $position) === 0 ? true : false;
        }

        public function relseek(int $position): bool {
            $this->assertOpen();
            
            return fseek($this->resource, $position, SEEK_CUR) === 0 ? true : false;
        }
        
        public function close(): bool {
            $status = true;

            if($this->resource !== NULL ? ($status = fclose($this->resource)) : false)
                $this->resource = null;

            return $status;
        }

        public function assertOpen(string $message = null): void {
            if(!$this->isOpen())
                throw new \Error($message ?: \Str::format(
                    "{} is required to be open.",
                    static::class
                ));
        }
    }
}

?>