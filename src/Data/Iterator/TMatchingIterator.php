<?php

namespace Slate\Data\Iterator {

use Generator;

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

        public function search(string $search): Generator {
            $line    = 0;

            while(!$this->isEof()) {
                if($this->current() === "\n")
                    $line++;

                if($this->match($search))
                    yield [
                        "pointer" => $this->tell(),
                        "line"    => $line
                    ];

                $this->next();
            }
        }

        public function count(string $search): int {
            $count = 0;

            while(!$this->isEof()) {
                if($this->match($search))
                    $count++;

                $this->next();
            }

            return $count;
        }
    }
}

?>