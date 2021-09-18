<?php

namespace Slate\IO {
    use Slate\Exception\PathNotFoundException;
    use Slate\Exception\IOException;
    use Slate\Data\Iterator\IExtendedIterator;
    use Slate\Data\Iterator\IAnchoredIterator;
    use Slate\Utility\TFactory;

    class Stream extends StreamBase implements IExtendedIterator, IAnchoredIterator, IStreamWriteable, IStreamReadable {
        public const FACTORY = StreamFactory::class;

        use TFactory;

        use TStreamReadable;
        use TStreamWriteable;
    }
}

?>