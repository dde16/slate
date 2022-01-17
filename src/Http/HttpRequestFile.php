<?php
namespace Slate\Http {
    use Slate\IO\File;
    use Slate\Neat\Attribute\ReadOnly;

    class HttpRequestFile extends HttpFile {
        protected string $httpBasename;
        protected int    $httpError;
        protected int    $httpSize;

        public function __construct(
            string $field,
            string $basename,
            string $path,
            string $mime,
            int    $error,
            int    $size
        ) {
            parent::__construct($field, $path);

            $this->httpBasename = $basename;

            $this->httpMime = $mime;
            $this->httpError = $error;
            $this->httpSize = $size;
        }

        public function getHttpSize(): int {
            return $this->httpSize;
        }

        public function getHttpError(): int {
            return $this->httpError;
        }

        public function getHttpBasename(): string {
            return $this->httpBasename;
        }
    }
}


?>