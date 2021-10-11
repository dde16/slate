<?php

namespace Slate\Sql\Clause {
    trait TSqlRenameAuxiliariesClause {
        protected array $renames = [];
        
        protected function _rename(string $type, string $from, string $to): static {
            $this->renames[] = [$type, $from, $to];

            return $this;
        }
        public function renameColumn(string $from, string $to): static {
            return $this->_rename("COLUMN", $from, $to);
        }

        public function renameIndex(string $from, string $to): static {
            return $this->_rename("INDEX", $from, $to);
        }

        public function renameKey(string $from, string $to): static {
            return $this->_rename("KEY", $from, $to);
        }

        public function buildRenameAuxiliariesClause(): ?string {
            return
                !\Arr::isEmpty($this->renames)
                    ? \Arr::join(
                        \Arr::map(
                            $this->renames,
                            fn($entry) => "RENAME " . \Arr::join($entry, " ")
                        ),
                        " "
                    )
                    : null;
        }
    }
}

?>