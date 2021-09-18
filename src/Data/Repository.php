<?php

namespace Slate\Data {

    use DateInterval;
    use DateTimeInterface;
    use Slate\Data\Repository\IRepository;

/**
     * The Cache boilerplate class.
     */
    abstract class Repository implements IRepository {
        protected bool      $autoforget;

        public function __construct(bool $autoforget = false) {
            $this->autoforget = $autoforget;
        }

        public function forever(string $key, mixed $value): void {
            $this->until($key, $value);
        }
        
        public function put(string $key, mixed $value, DateTimeInterface|DateInterval|int|float|null $ttl = null): void {
            $this->until($key, $value, $ttl !== null ? \Real::fromDateTimeSpan($ttl) : null);
        }
    }
}

?>