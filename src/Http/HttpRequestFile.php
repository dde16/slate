<?php
namespace Slate\Http {
    use Slate\IO\File;

    class HttpRequestFile extends HttpFile {
        public string $httpFileName;
        public int    $httpError;

        public function __construct(
            string $field,
            string $filename,
            string $path,
            string $mime,
            int    $error
        ) {
            parent::__construct($field, $path);

            $this->httpFileName = $filename;

            $this->httpMime = $mime;
            $this->httpError = $error;
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