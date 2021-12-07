<?php

namespace Slate\Sql\Statement {
    use Slate\Sql\SqlStatement;

    class SqlLockTablesStatement extends SqlStatement {
        protected array $tables = [];

        public function tableRead(string $ref, bool $local = false): static {
            return $this->table($ref, "READ" . ($local ? " LOCAL" : ""));
        }

        public function tableWrite(string $ref, bool $lowPriority = false): static {
            return $this->table($ref, ($lowPriority ? "LOW_PRIORITY " : "") . "WRITE");
        }

        protected function table(string $ref, string $type): static {
            $this->tables[] = [$ref, $type];

            return $this;
        }

        public function build(): array {
            return [
                "LOCK TABLES",
                \Arr::join(
                    \Arr::map(
                        $this->tables,
                        fn($entry) => "{$entry[0]} {$entry[1]}"
                    ),
                    ", "
                )
            ];
        }
    }
}

?>