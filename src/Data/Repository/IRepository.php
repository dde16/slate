<?php declare(strict_types = 1);

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