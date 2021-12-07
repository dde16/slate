<?php
namespace Slate\Http {
    use Slate\IO\File;

    class HttpRequestFile extends HttpFile {
        protected string $httpFileName;
        protected int    $httpError;
        protected int    $httpSize;

        public function __construct(
            string $field,
            string $filename,
            string $path,
            string $mime,
            int    $error,
            int    $size
        ) {
            parent::__construct($field, $path);

            $this->httpFileName = $filename;

            $this->httpMime = $mime;
            $this->httpError = $error;
            $this->httpSize = $size;
        }

        public function getHttpError(): int {
            return $this->httpError;
        }

        public function getFileName(): int {
            return $this->httpFileName;
        }
    }
}


?>