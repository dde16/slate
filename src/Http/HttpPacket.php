<?php

namespace Slate\Http {
    use Slate\Data\Collection;
    use Slate\Neat\Attribute\Getter;
    use Slate\Neat\Model;

    class HttpPacket extends Model {
        protected Collection $headers;
        protected Collection $cookies;
        protected Collection $files;

        #[Getter("headers")]
        public function getHeaders(): Collection {
            return $this->headers;
        }

        #[Getter("cookies")]
        public function getCookies(): Collection {
            return $this->cookies;
        }

        #[Getter("files")]
        public function getFiles(): Collection {
            return $this->files;
        }

        public function getHeader(string $name): mixed {
            return $this->headers[$name];
        }
    }
}

?>