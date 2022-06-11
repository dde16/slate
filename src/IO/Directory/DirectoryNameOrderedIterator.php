<?php

namespace Slate\IO\Directory {
    class DirectoryNameOrderedIterator extends DirectoryOrderedIterator {
        protected function order(): void {
            $files = \Arr::fromGenerator($this->directory->walk());

            usort(
                $files,
                function(array $file1, array $file2): int {
                    if($this->order === "asc")
                        return intval($file1["basename"] > $file2["basename"]);
                    
                    if($this->order === "desc")
                        return intval($file1["basename"] < $file2["basename"]);
                }
            );
            
            $this->items = $files;
        }
    }
}

?>