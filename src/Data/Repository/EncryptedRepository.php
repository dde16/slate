<?php declare(strict_types = 1);

namespace Slate\Data\Repository {
    use Slate\Crypto\Cipher;
    use Slate\Data\Repository\IRepositorySerialized;
    use Slate\Data\Repository;
    use Slate\Data\SerializedRepository;

abstract class EncryptedRepository extends SerializedRepository {
        public function __construct(
            string $method,
            string $key,
            bool $autoforget = false
        ) {
            $this->cipher = new Cipher($method, $key);
        }

        public function serialize(mixed $value): string {
            list($ciphertext, $cipheriv)
                = $this->cipher->encrypt(
                    parent::serialize($value)
                );

            return $cipheriv.$ciphertext;
        }

        public function deserialize(string $ciphertext): mixed {
            $cipheriv   = \Str::slice($ciphertext, 0, $this->cipher->ivlen);
            $ciphertext = \Str::slice($ciphertext, $this->cipher->ivlen);

            return parent::deserialize($this->cipher->decrypt($ciphertext, iv: $cipheriv));
        }
    }
}

?>