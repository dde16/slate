<?php declare(strict_types = 1);

namespace Slate\IO {
    use Slate\Data\Iterator\IExtendedIterator;
    use Slate\Data\Iterator\IAnchoredIterator;
    use Slate\IO\Contract\IStreamReadable;
    use Slate\IO\Contract\IStreamWriteable;
    use Slate\IO\Trait\TStreamReadable;
    use Slate\IO\Trait\TStreamWriteable;
    use Slate\Utility\TFactory;

    class Stream extends StreamBase implements IExtendedIterator, IAnchoredIterator, IStreamWriteable, IStreamReadable {
        public const FACTORY = StreamFactory::class;

        use TFactory;

        use TStreamReadable;
        use TStreamWriteable;
    }
}

?>