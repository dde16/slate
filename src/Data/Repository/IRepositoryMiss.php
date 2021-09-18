<?php

namespace Slate\Data\Repository {
    interface IRepositoryHit {
        /**
         * An event when a Cache item has not been found.
         *
         * @param  mixed $key
         * @param  mixed $derived
         * @return mixed
         */
        function miss(string $key, string $derived): mixed;
    }
}

?>