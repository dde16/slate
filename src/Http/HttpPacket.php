<?php declare(strict_types = 1);

namespace Slate\Http {
    use Slate\Data\Collection;
    use Slate\Neat\Attribute\Getter;
    use Slate\Neat\Attribute\SetOnce;
    use Slate\Neat\Model;

    class HttpPacket extends Model {
        #[SetOnce]
        protected Collection $headers;
        
        #[SetOnce]
        protected Collection $cookies;

        #[SetOnce]
        protected Collection $files;

        public function getHeader(string $name): mixed {
            return $this->headers[$name];
        }
    }
}

?>