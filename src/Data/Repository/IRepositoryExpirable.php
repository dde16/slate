<?php declare(strict_types = 1);

namespace Slate\Data\Repository {

    use Closure;
    use DateInterval;
    use DateTimeInterface;

    interface IRepositoryExpirable {
        /**
         * Modify an item.
         * 
         * @param string  $key
         * @param Closure $callback
         * 
         * @return void
         */
        function modify(string $key, Closure $callback, DateTimeInterface|DateInterval|int|null $ttl = null): bool;
        
        /**
         * Store a value in the cache by a given key until a given time.
         * 
         * @param string $key
         * @param mixed $value
         * 
         * @return void
         */
        function until(string $key, mixed $value, DateTimeInterface|DateInterval|int|float|null $ttl = null): void;

        /**
         * Store a value in the cache by a given key.
         * 
         * @param string $key
         * @param mixed $value
         * 
         * @return void
         */
        function put(string $key, mixed $value, DateTimeInterface|DateInterval|int|float|null $ttl = null): void;
        
        /**
         * Check if an item by a given key has expired.
         * 
         * @param string $key
         * 
         * @return bool
         */
        function expired(string $key): bool;
    }
}

?>