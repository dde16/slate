<?php

namespace Slate\Crypto {

    use Slate\Neat\Attribute\Getter;
    use Slate\Neat\Attribute\Setter;
    use Slate\Neat\Model;

    /**
     * A class to encrypt and decrypt data.
     * Persists the method and key.
     */
    class Cipher extends Model {
        /**
         * @var string
         */
        protected string $method;

        /**
         * @var int
         */
        protected int       $ivlen;
        
        /**
         * @var string
         */
        protected string    $key;

        public function __construct(string $method, string $key) {
            parent::__construct();
            $this->setMethod($method);
            $this->key = $key;
        }

        #[Getter("ivlen")]
        public function getIvLength(): int {
            return $this->ivlen;
        }
        
        #[Setter("method")]
        public function setMethod(string $method): void {
            $methods = openssl_get_cipher_methods();

            if(\Arr::contains($methods, \Str::lower($method))) {
                $this->method = $method;
                $this->ivlen = openssl_cipher_iv_length($method);
            }
            else {
                throw new \InvalidArgumentException("Cipher method '{$method}' is not supported.");
            }
        }
        
        #[Getter("method")]
        public function getMethod(): string {
            return $this->method;
        }
                
        /**
         * Encrypt plaintext.
         * 
         * @param string $plaintext
         * @param string|null $iv
         * @param int $options
         * @param string|null &$tag
         * @param string|null $aad
         * @param int $tagLength
         * 
         * @return array
         */
        public function encrypt(
            string $plaintext, 
            string $iv = null, 
            int $options = OPENSSL_RAW_DATA,
            string &$tag = null,
            string $aad = null,
            int $tagLength = 16
        ): array {
            $iv        = $iv ?: openssl_random_pseudo_bytes($this->ivlen);

            return [openssl_encrypt(
                $plaintext,
                $this->method,
                $this->key,
                $options,
                $iv,
                $tag,
                $aad,
                $tagLength
            ), $iv];
        }

        /**
         * Encrypt ciphertext.
         * 
         * @param string $ciphertext
         * @param string|null $iv
         * @param int $options
         * @param string|null &$tag
         * @param string|null $aad
         * @param int $tagLength
         * 
         * @return array
         */
        public function decrypt(
            string $ciphertext,
            string $iv,
            int $options = OPENSSL_RAW_DATA,
            string &$tag = null,
            string $aad = null,
            int $tagLength = 16
        ): string|false {
            return openssl_decrypt(
                $ciphertext,
                $this->method,
                $this->key,
                $options,
                $iv,
                $tag,
                $aad
            );

        }
    }
}

?>