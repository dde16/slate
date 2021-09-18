<?php

namespace Slate\Data\Iterator {
    use Iterator;

    /**
     * An iterator which includes the ability to iterate
     * backwards as well as forwards.
     */
    interface IExtendedIterator extends Iterator {
        function prev(): void;
    }
}

?>