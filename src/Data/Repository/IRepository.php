<?php

namespace Slate\Data\Repository {

    use Closure;
    use DateTimeInterface;
    use DateInterval;
    
    interface IRepository {
        /**
         * Store an item in the cache forever.
         * 
         * @param string $key
         * @param mixed $value
         * 
         * @return void
         */
        function forever(string $key, mixed $value): void;

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
         * Get a value from the cache by a given key.
         * 
         * @param string $key
         * 
         * @return mixed
         */
        function pull(string $key, mixed $default = null): mixed;

        /**
         * Check if an item exists by a given key.
         * 
         * @param string $key
         * 
         * @return bool
         */
        function has(string $key): bool;

        /**
         * Check if an item by a given key has expired.
         * 
         * @param string $key
         * 
         * @return bool
         */
        function expired(string $key): bool;
    
        /**
         * Remove a cache item by its given key.
         * 
         * @return bool
         */
        function forget(string $key): bool;

        /**
         * Clear the cache.
         * 
         * @return bool
         */
        function flush(): bool;
    
        /**
         * Turn a key into a key used for indexing.
         * 
         * @param string $key
         * 
         * @return string
         */
        function derive(string $key): string;
    }
}

?>