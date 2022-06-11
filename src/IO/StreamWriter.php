<?php declare(strict_types = 1);

namespace Slate\IO {
    use Slate\Exception\PathNotFoundException;
    use Slate\Exception\IOException;
    use Slate\IO\Contract\IStreamWriteable;
    use Slate\IO\Trait\TStreamWriteable;

    class StreamWriter extends StreamBase implements IStreamWriteable {
        use TStreamWriteable;
    }
}

?>