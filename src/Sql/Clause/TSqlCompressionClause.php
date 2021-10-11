<?php

namespace Slate\Sql\Clause {
    trait TSqlCompressionClause {

        protected ?string $compression = null;

        public function lz4(): static {
            $this->compression = "LZ4";

            return $this;
        }

        public function zlib(): static {
            $this->compression = "ZLIB";

            return $this;
        }

        public function uncompressed(): static {
            $this->compression = "NONE";

            return $this;
        }

        public function buildCompressionClause(): ?string {
            return $this->compression !== null ? ("COMPRESSION {$this->compression}") : null;
        }
    }
}

?>