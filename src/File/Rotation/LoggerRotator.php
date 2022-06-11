<?php declare(strict_types = 1);

namespace Slate\File\Rotation {
    use Closure;
    use Slate\IO\File;

    class LoggerRotator extends FileRotator {
        protected int $maxSize;

        public function __construct(?Closure $mutator = null, ?Closure $filter = null, int $maxSize = null) {
            parent::__construct($mutator, $filter);

            $this->maxSize = $maxSize ?? \Integer::bytes(5, \Integer::MEGABYTE);
        }

        public function filter(string $path): bool {
            return File::getSizeOf($path) > $this->maxSize;
        }
    }
}

?>