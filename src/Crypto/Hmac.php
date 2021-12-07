<?php

namespace Slate\Crypto {
    use Slate\Neat\Model;

    class Hmac extends Hash {
        public function __construct(
            string $algorithm,
            string $key,
            string $data = null
        ) {
            $this->setAlgorithm($algorithm);

            $this->hashContext = hash_init($this->algorithm, HASH_HMAC, $key);

            if($data) $this->update($data);
            
        }

        public static function getAlgorithms(): array {
            return hash_hmac_algos();
        }
    }
}

?>