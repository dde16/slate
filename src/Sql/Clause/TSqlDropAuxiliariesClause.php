<?php

namespace Slate\Sql\Clause {

    trait TSqlDropAuxiliariesClause {

        protected array $drops     = [];

        protected function drop(string $type, ?string $identifier = null): static {
            $this->drops[] = [$type, $identifier];

            return $this;
        }

        public function dropConstraint(string $symbol): static {
            return $this->drop("CONSTRAINT", $symbol);
        }

        public function dropCheck(string $symbol): static {
            return $this->drop("CHECK", $symbol);
        }

        public function dropColumn(string $name): static {
            return $this->drop("COLUMN", $name);
        }

        public function dropIndex(string $name): static {
            return $this->drop("INDEX", $name);
        }

        public function dropKey(string $name): static {
            return $this->drop("KEY", $name);
        }

        public function dropPrimaryKey(): static {
            return $this->drop("PRIMARY KEY");
        }

        public function dropForeignKey(string $name): static {
            return $this->drop("FOREIGN KEY", $name);
        }

        public function buildDropAuxiliariesClause(): ?string {
            return 
                !\Arr::isEmpty($this->drops)
                    ? \Arr::join(
                        \Arr::map(
                            $this->drops,
                            fn($entry) => \Arr::join(\Arr::filter($entry),  "")
                        )
                    )
                    : null;
        }
    }
}
