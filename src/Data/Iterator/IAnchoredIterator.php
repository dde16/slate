<?php

namespace Slate\Data\Iterator {
    use Iterator;

    /**
     * An extended form iterator to be able to save ('anchor') many
     * pointers and revert to them at a later date. This is useful
     * for general iteration and backtracking applications.
     */
    interface IAnchoredIterator extends Iterator {
        function anchor(): void;
        function revert(): void;
    }
}

?>