<?php

namespace Slate\IO {
    use Slate\Data\Iterator\IExtendedIterator;
    use Slate\Data\Iterator\IAnchoredIterator;
    use Slate\Data\Iterator\IMatchingIterator;

// IAnchoredIterator

    class StreamReader extends StreamBase implements IAnchoredIterator, IMatchingIterator, IExtendedIterator, IStreamReadable {
        use TStreamReadable;
    }
}

?>