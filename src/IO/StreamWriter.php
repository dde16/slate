<?php

namespace Slate\IO {
    use Slate\Exception\PathNotFoundException;
    use Slate\Exception\IOException;

    class StreamWriter extends StreamBase implements IStreamWriteable {
        use TStreamWriteable;
    }
}

?>