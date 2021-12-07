<?php

namespace Slate\Crypto {

    use HashContext;
    use Slate\Neat\Attribute\Getter;
    use Slate\Neat\Attribute\Setter;
    use Slate\Neat\Model;

    class Hash extends Model {
        protected string $algorithm;

        protected HashContext $hashContext;

        public function __construct(string $algorithm, string $data = null) {
            $this->setAlgorithm($algorithm);

            $this->hashContext = hash_init($this->algorithm);

            if($data) $this->update($data);
        }

        public function equals(string $operand, bool $safe = true): bool {
            return $safe ? hash_equals($this->toHex(), $operand) : $this->digest === $operand; 
        }

        public function update(string $data): bool {
            return hash_update($this->hashContext, $data);
        }

        #[Setter("algorithm")]
        public function setAlgorithm(string $algorithm): void {
            if($this->isAlgorithm($algorithm)  || $this->isAlgorithm($algorithm, \Str::lower($algorithm))) {
                $this->algorithm = $algorithm;
            }
            else {
                throw new \InvalidArgumentException("Hash method '{$algorithm}' is not supported.");
            }
        }

        public function toBytes(): string {
            return hash_final($this->hashContext, binary: true);
        }

        public function toHex(): string {
            return hash_final($this->hashContext, binary: false);
        }

        public function toBase64(): string {
            return base64_encode($this->toBytes());
        }

        #[Getter("algorithm")]
        public function getAlgorithm(): string {
            return $this->algorithm;
        }

        public static function getAlgorithms(): array {
            return hash_algos();
        }

        public static function isAlgorithm(string $algorithm): bool {
            return \Arr::contains(static::getAlgorithms(), $algorithm);
        }
    }
}

?>