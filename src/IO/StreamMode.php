<?php

namespace Slate\IO {

use Slate\Data\IStringForwardConvertable;
    use Slate\Data\TStringNativeForwardConvertable;

class StreamMode implements IStringForwardConvertable {
        use TStringNativeForwardConvertable;

        /** Contains all file modes which truncate. */
        const TRUNCATES = [
            File::WRITE_ONLY,
            File::WRITE_PLUS,

            File::WRITE_EXCLUSIVE,
            File::READ_WRITE_EXCLUSIVE
        ];

        /** Contains all file modes which read. */
        const READS = [
            File::READ_ONLY,
            File::READ_WRITE,
            File::READ_WRITE_EXCLUSIVE,
            File::WRITE_PLUS,
            File::APPEND_SEEKABLE
        ];

        /** Contains all file modes which write */
        const WRITES = [
            File::WRITE_EXCLUSIVE,
            File::WRITE_ONLY,
            File::WRITE_PLUS,
            File::READ_WRITE_EXCLUSIVE,
            File::READ_WRITE
        ];
        
        /** Contains all file modes which seek */
        const SEEKS = [
            File::READ_ONLY,
            File::READ_WRITE,
            File::READ_WRITE_EXCLUSIVE,
            File::WRITE_ONLY,
            File::WRITE_EXCLUSIVE,
            File::WRITE_PLUS,
            File::APPEND_SEEKABLE
        ];

        const READ_ONLY            = "r";
        const READ_WRITE           = "r+";
        const READ_WRITE_EXCLUSIVE = "x+";
        
        const WRITE_ONLY           = "w";
        const WRITE_EXCLUSIVE      = "x";
        const WRITE_PLUS           = "w+";

        const APPEND               = "a";
        const APPEND_SEEKABLE      = "a+";

        protected string $mode;

        public function __construct(string $mode) {
            $this->mode = $mode;
        }

        public function isReadable(): bool {
            return \Arr::contains(static::READS, $this->mode);
        }

        public function isWritable(): bool {
            return \Arr::contains(static::WRITES, $this->mode);
        }

        public function isTruncatable(): bool {
            return \Arr::contains(static::TRUNCATES, $this->mode);
        }

        public function isSeekable(): bool {
            return \Arr::contains(static::SEEKS, $this->mode);
        }

        public function toString(): string {
            return $this->mode;
        }
    }
}

?>