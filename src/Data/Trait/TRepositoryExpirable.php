<?php declare(strict_types = 1);

namespace Slate\Data\Trait {

    use DateInterval;
    use DateTimeInterface;

    trait TRepositoryExpirable {
        public function forever(string $key, mixed $value): void {
            $this->until($key, $value);
        }
        
        public function put(string $key, mixed $value, DateTimeInterface|DateInterval|int|float|null $ttl = null): void {
            $this->until($key, $value, $ttl !== null ? \Real::fromDateTimeSpan($ttl) : null);
        }
    }
}

?>