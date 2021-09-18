<?php

namespace Slate\Data\Iterator {
    interface IMatchingIterator {
        
        /**
         * Checks if the string provided matches from the current pointer.
         * This function is intended for larger strings as a low-memory solution
         * thus should not be used for small strings.
         * 
         * @param string $match The string to be matched at the start of the source
         * @param bool   $revert Whether to revert on an unsuccessful match
         */
        public function complexMatch(string $match, bool $revert = true);

        
        /**
         * Checks if the string provided matches from the current pointer.
         * 
         * @param string $match The string to be matched at the start of the source
         * @param bool   $revert Whether to revert on an unsuccessful match
         * 
         * @return bool
         */
        public function match(string $match): bool;
    }
}

?>