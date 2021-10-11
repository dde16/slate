<?php

namespace Slate\Http {
    use Slate\IO\File;

    abstract class HttpFile extends File {
        public string $httpField;
        public string $httpMime;

        public function __construct(
            string $field,
            string $path,
            string $mime = null
        ) {
            parent::__construct($path);

            $this->httpField = $field;

            $this->httpMime = $mime ?: "application/octet-stream";
        }

        public function getHttpField(): string {
            return $this->httpField;
        }

        public function getHttpMime(): string {
            return $this->httpMime;
        }
    }
}

?>