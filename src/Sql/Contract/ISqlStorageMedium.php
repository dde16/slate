<?php declare(strict_types = 1);

namespace Slate\Sql {
    interface ISqlStorageMedium {

        /**
         * Drop the current medium.
         *
         * @return void
         */
        function drop(): void;

        /**
         * Create the current medium.
         *
         * @return void
         */
        function create(): void;

        /**
         * Check if the current medium exists.
         *
         * @return void
         */
        function exists(): bool;
    }
}

?>