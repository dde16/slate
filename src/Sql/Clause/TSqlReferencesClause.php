<?php

namespace Slate\Sql\Clause {
    trait TSqlReferencesClause {
        protected ?string $ref = null;
        protected ?string $col = null;

        protected ?string $match = null;

        protected ?string $onDelete = null;
        protected ?string $onUpdate = null;

        public function references(string $ref, string $col): static {
            $this->ref = $ref;
            $this->col = $col;

            return $this;
        }

        public function onUpdate(string $onUpdate): static {
            $this->onUpdate = $onUpdate;

            return $this;
        }

        public function onUpdateRestrict(): static {
            return $this->onUpdate("RESTRICT");
        }
        
        public function onDeleteRestrict(): static {
            return $this->onDelete("RESTRICT");
        }

        public function onUpdateCascade(): static {
            return $this->onUpdate("CASCADE");
        }
        
        public function onDeleteCascade(): static {
            return $this->onDelete("CASCADE");
        }

        public function onUpdateSetNull(): static {
            return $this->onUpdate("SET NULL");
        }
        
        public function onDeleteSetNull(): static {
            return $this->onDelete("SET NULL");
        }

        public function onUpdateNoAction(): static {
            return $this->onUpdate("NO ACTION");
        }
        
        public function onDeleteNoAction(): static {
            return $this->onDelete("NO ACTION");
        }

        public function onUpdateSetDefault(): static {
            return $this->onUpdate("SET DEFAULT");
        }
        
        public function onDeleteSetDefault(): static {
            return $this->onDelete("SET DEFAULT");
        }


        public function onDelete(string $onDelete): static {
            $this->onDelete = $onDelete;

            return $this;
        }

        public function match(string $match): static {
            $this->match = $match;

            return $this;
        }

        public function matchFull(): static {
            return $this->match("FULL");
        }

        public function matchPartial(): static {
            return $this->match("PARTIAL");
        }

        public function matchSimple(): static {
            return $this->match("SIMPLE");
        }

        public function buildReferencesClause(): ?string {
            return $this->ref !== null ? \Arr::join(\Arr::filter([
                "REFERENCES",
                $this->ref,
                \Str::wrapc($this->col, "()"),
                ($this->match ? "MATCH {$this->match}" : null),
                ($this->onDelete ? "ON DELETE {$this->onDelete}" : null),
                ($this->onUpdate ? "ON UPDATE {$this->onUpdate}" : null)
            ]), " ") : null;
        }
    }
}

?>