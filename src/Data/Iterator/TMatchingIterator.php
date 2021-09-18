<?php

namespace Slate\Data\Iterator {
    /**
     * Provides simple (optionally high-efficiency) matching for iterators.
     */
    trait TMatchingIterator {
        /** @see IMatchingIterator::complexMatch() */
        public function complexMatch(string $match, bool $revert = true) {
            $matchLength = strlen($match);
            $matchIndex  = 0;
            
            $matched = true;
            
            $this->anchor();
            
            while($matched && $matchIndex !== $matchLength) {
                if(($strChar = $this->current()) !== null) {
                    $cmpChar = substr($match, $matchIndex++, 1);
            
                    if(!($matched &= ($strChar === $cmpChar))) {
                        if($revert) $this->revert();

                        return false;
                    }
                    else {
                        $this->next();
                        $matched &= $this->valid();
                    }
                }
            }

            $this->anchor--;

            return true;
        }

        /** @see IMatchingIterator::match() */
        public function match(string $match): bool {
            $matchLength = strlen($match);

            $this->anchor();

            $data = $this->read($matchLength);

            if($data !== null) {
                if(($data === $match)) {
                    return true;
                }
            }
            $this->revert();

            return false;
        }
    }
}

?>