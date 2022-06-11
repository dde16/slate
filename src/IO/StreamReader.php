<?php declare(strict_types = 1);

namespace Slate\IO {
    use Slate\Data\Iterator\IExtendedIterator;
    use Slate\Data\Iterator\IAnchoredIterator;
    use Slate\Data\Iterator\IMatchingIterator;
    use Slate\IO\Contract\IStreamReadable;
    use Slate\IO\Trait\TStreamReadable;

    class StreamReader extends StreamBase implements IAnchoredIterator, IMatchingIterator, IExtendedIterator, IStreamReadable {
        use TStreamReadable;
    }
}

?>