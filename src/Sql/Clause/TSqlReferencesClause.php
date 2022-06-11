<?php declare(strict_types = 1);

namespace Slate\Sql\Clause {
    trait TSqlReferencesClause {
        public ?string $foreignSchema = null;
        public ?string $foreignTable = null;
        public ?string $foreignColumn = null;

        public string $onDelete = "RESTRICT";
        public string $onUpdate = "RESTRICT";

        public function references(string $schema, string $table, string $column): static {
            $this->foreignSchema = $schema;
            $this->foreignTable = $table;
            $this->foreignColumn = $column;

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

        public function buildReferencesClause(): ?string {
            return $this->foreignSchema !== null ? \Arr::join(\Arr::filter([
                "REFERENCES",
                $this->conn()->wrap($this->foreignSchema, $this->foreignTable),
                \Str::wrapc($this->conn()->wrap($this->foreignColumn), "()"),
                ($this->onDelete ? "ON DELETE {$this->onDelete}" : null),
                ($this->onUpdate ? "ON UPDATE {$this->onUpdate}" : null)
            ]), " ") : null;
        }
    }
}

?>