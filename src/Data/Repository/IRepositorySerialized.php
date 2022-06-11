<?php declare(strict_types = 1);

namespace Slate\Data\Repository {
    interface IRepositorySerialized {    
        /**
         * Turn a value into its string equivalent, to be stored in a storage medium.
         * 
         * @param mixed $value
         * 
         * @return string
         */
        function serialize(mixed $value): string;

        /**
         * Turn the stored value from a storage medium into a PHP value.
         * 
         * @param string $value
         * 
         * @return string
         */
        function deserialize(string $value): mixed;
    }
}

?>