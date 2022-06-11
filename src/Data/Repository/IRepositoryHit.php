<?php declare(strict_types = 1);

namespace Slate\Data\Repository {
    interface IRepositoryHit {
        /**
         * An event when a Cache item has been found.
         *
         * @param  mixed $key
         * @param  mixed $derived
         * @param  mixed $value
         * @param  mixed $meta
         * @return void
         */
        function hit(string $key, string $derived, mixed &$value, array $meta): void;
    }
}