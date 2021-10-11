<?php

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

        /**
         * Load the current medium, with options to load its children.
         *
         * @param array $options
         * @return void
         */
        function load(array $options = []): void;
    }
}

?>