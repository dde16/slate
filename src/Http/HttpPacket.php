<?php

namespace Slate\Http {
    use Slate\Data\Collection;
    use Slate\Neat\Attribute\Getter;
    use Slate\Neat\Attribute\ReadOnly;
    use Slate\Neat\Model;

    class HttpPacket extends Model {
        #[ReadOnly]
        protected Collection $headers;
        
        #[ReadOnly]
        protected Collection $cookies;

        /**
         * @var Collection[]HttpFile
         */
        #[ReadOnly]
        protected Collection $files;

        public function getHeader(string $name): mixed {
            return $this->headers[$name];
        }
    }
}

?>