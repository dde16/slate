<?php declare(strict_types = 1);

namespace Slate\Sql\Clause {
    trait TSqlRowFormatClause {
        protected ?string $rowFormat = null;

        protected function rowFormat(string $rowFormat): static {
            $this->rowFormat = $rowFormat;

            return $this;
        }

        public function dynamic(): static {
            return $this->rowFormat("DYNAMIC");
        }

        public function compact(): static {
            return $this->rowFormat("COMPACT");
        }

        public function redundant(): static {
            return $this->rowFormat("REDUNDANT");
        }

        public function compressed(): static {
            return $this->rowFormat("COMPRESSED");
        }
    }
}

?>