<?php declare(strict_types = 1);

namespace Slate\Http {
    use Slate\IO\File;

    abstract class HttpFile extends File {
        /**
         * The field name in the post body for the file.
         *
         * @var string
         */
        protected string $httpField;

        /**
         * File mime type.
         *
         * @var string
         */
        protected string $httpMime;

        public function __construct(
            string $field,
            string $path,
            string $mime = null
        ) {

            parent::__construct($path);

            $this->httpField = $field;

            $this->httpMime = $mime ?? "application/octet-stream";
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