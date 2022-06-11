<?php declare(strict_types = 1);

namespace Slate\IO {

    use RuntimeException;
    use Slate\Data\Contract\IStringForwardConvertable;
    use Slate\Data\TStringNativeForwardConvertable;

    class StreamMode implements IStringForwardConvertable {
        use TStringNativeForwardConvertable;

        /** Contains all file modes which truncate. */
        const TRUNCATES = [
            StreamMode::WRITE_ONLY,
            StreamMode::WRITE_PLUS,

            StreamMode::WRITE_EXCLUSIVE,
            StreamMode::READ_WRITE_EXCLUSIVE
        ];

        /** Contains all file modes which read. */
        const READS = [
            StreamMode::READ_ONLY,
            StreamMode::READ_WRITE,
            StreamMode::READ_WRITE_EXCLUSIVE,
            StreamMode::WRITE_PLUS,
            StreamMode::APPEND_SEEKABLE
        ];

        /** Contains all file modes which write */
        const WRITES = [
            StreamMode::WRITE_EXCLUSIVE,
            StreamMode::WRITE_ONLY,
            StreamMode::WRITE_PLUS,
            StreamMode::READ_WRITE_EXCLUSIVE,
            StreamMode::READ_WRITE
        ];
        
        /** Contains all file modes which seek */
        const SEEKS = [
            StreamMode::READ_ONLY,
            StreamMode::READ_WRITE,
            StreamMode::READ_WRITE_EXCLUSIVE,
            StreamMode::WRITE_ONLY,
            StreamMode::WRITE_EXCLUSIVE,
            StreamMode::WRITE_PLUS,
            StreamMode::APPEND_SEEKABLE
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
            if(!\Arr::contains([static::READ_ONLY, static::READ_WRITE, static::READ_WRITE_EXCLUSIVE, static::WRITE_ONLY, static::WRITE_EXCLUSIVE, static::WRITE_PLUS, static::APPEND, static::APPEND_SEEKABLE], $mode))
                throw new RuntimeException("Invalid Stream Mode '$mode'.");

            $this->mode = $mode;
        }

        public function isReadable(): bool {
            return \Arr::contains(static::READS, $this->mode);
        }

        public function isWritable(): bool {
            return \Arr::contains(static::WRITES, $this->mode);
        }

        public function isWriteable(): bool {
            return $this->isWritable();
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