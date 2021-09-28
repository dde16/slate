<?php

namespace Slate\Crypto {
    use Slate\IO\File;

    class EncryptedFile extends File {
        /**
         * @var Cipher
         */
        protected Cipher $cipher;

        /**
         * @var string|null
         */
        protected ?string $iv;

        /**
         * @var int
         */
        protected int $blocksize;

        public function __construct(string $path, string $method, string $key, string $mode = null, int $chunksize = null) {
            parent::__construct($path, $mode);

            $this->cipher = new Cipher($method, $key);
            $this->blocksize = $this->cipher->ivlen;
        }

        public function open(string $mode = null): void {
            $exists    = \Path::exists($this->path);
            
            parent::open($mode);

            $truncates = \Arr::contains(File::TRUNCATES, $this->currentMode);
            $reads     = \Arr::contains(File::READS, $this->currentMode);

            if($truncates) {
                parent::write($this->iv = openssl_random_pseudo_bytes($this->cipher->ivlen));
            }
            else if($exists && $reads) {
                parent::seek(0);

                if(($iv = $this->read($this->cipher->ivlen, eofNull: true)) !== null) {
                    $this->iv = $iv;
                    parent::seek($this->cipher->ivlen);
                }
                else {
                    throw new \Error("Unable to read the complete initialisation vector (which should be at the start of the file).");
                }
            }
        }

        public function seek(int $position): bool {
            return parent::seek(($this->cipher->ivlen) + $position);
        }

        public function write(string $data, int $size = null): void {
            foreach(\Str::chunk($data, $this->blocksize) as $chunk)
                $this->writeblock($chunk);
            
        }

        public function rewind(): void {
            parent::seek($this->cipher->ivlen);
        }

        public function writeblock(string $data): void {
            if(strlen($data) >= ($this->blocksize)) {
                throw new \Error("Plaintext blocks must be at most " . strval($this->blocksize-1) . " characters in length as otherwise it will overflow.");
            }

            parent::write(
                $this->cipher->encrypt($data, iv: $this->iv)[0]
            );
        }

        public function readblock(): ?string {
            if(($block = $this->read($this->blocksize, eofNull: true)) !== null) {
                $plaintext = $this->cipher->decrypt($block, iv: $this->iv);

                if($plaintext === false)
                    throw new \Error(\Str::format(
                        "Unable to decrypt cipher block at position {}.",
                        $this->tell()
                    ));

                return $plaintext;
            }
            else {
                throw new \Error(\Str::format(
                    "Unable to read cipher block at position {}.",
                    $this->tell()
                ));
            }

            return null;
        }
    }
}

?>