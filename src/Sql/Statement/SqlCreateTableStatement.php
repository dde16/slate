<?php

namespace Slate\Sql\Statement {

    use Slate\Sql\Clause\TSqlCharacterSetClause;
    use Slate\Sql\Clause\TSqlCollateClause;
    use Slate\Sql\Clause\TSqlCommentClause;
    use Slate\Sql\Clause\TSqlEngineAttributeClause;
    use Slate\Sql\Clause\TSqlMediumClause;
    use Slate\Sql\Expression\SqlColumnBlueprint;
    use Slate\Sql\Expression\SqlConstraintBlueprint;
    use Slate\Sql\Modifier\TSqlIfNotExistsModifier;
    use Slate\Sql\Modifier\TSqlIgnoreModifier;
    use Slate\Sql\Modifier\TSqlReplaceModifier;
    use Slate\Sql\Modifier\TSqlTemporaryModifier;
    use Slate\Sql\SqlStatement;

    class SqlCreateTableStatement extends SqlStatement {
        use TSqlTemporaryModifier;
        use TSqlIgnoreModifier;
        use TSqlReplaceModifier;
        use TSqlIfNotExistsModifier;
        use TSqlCharacterSetClause;
        use TSqlCollateClause;
        use TSqlCommentClause;
        use TSqlEngineAttributeClause;

        protected string $name;

        protected array  $columns;
        protected array  $constraints;

        protected ?string $like = null;

        protected ?int $autoExtendSize = null;
        protected ?int $autoIncrement = null;
        protected ?int $avgRowLength = null;

        protected ?bool $checksum = null;

        protected ?bool $delayKeyWrite = null;

        protected ?bool $encrypted = null;

        protected ?string $compression = null;

        public function delayKeyWrite(): static {
            $this->delayKeyWrite = true;

            return $this;
        }

        public function encrypted(bool $encrypted): static {
            $this->encrypted = $encrypted;

            return $this;
        }

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

        public function checksum(bool $checksum): static {
            $this->checksum = $checksum;

            return $this;
        }

        public function autoExtend(int $size): static {
            $this->autoExtendSize = $size;

            return $this;
        }

        public function autoIncrement(int $increment): static {
            $this->autoIncrement = $increment;

            return $this;
        }

        public function avgRow(int $length): static {
            $this->avgRowLength = $length;

            return $this;
        }

        public function like(string $like): ?string {
            $this->like = $like;

            return $like;
        }

        public function __construct(string $name) {
            $this->name = $name;
        }

        public function constraint(): SqlConstraintBlueprint {
            return new SqlConstraintBlueprint();
        }

        public function column(string $name): SqlColumnBlueprint {
            return new SqlColumnBlueprint($name);
        }

        public function build(): array {
            return [
                "CREATE",
                $this->buildTemporaryModifier(),
                "TABLE",
                $this->buildIfNotExistsModifier(),
                $this->name,
                $this->like ? "LIKE {$this->like}" : null,
                $this->checksum !== null ? ("CHECKSUM " . ($this->encrypted ? '1' : '0')) : null,
                $this->encrypted !== null ? ("ENCRYPTION " . ($this->encrypted ? 'Y' : 'N')) : null,
                $this->compression !== null ? ("COMPRESSION {$this->compression}") : null,
                $this->delayKeyWrite !== null ? ("DELAY_KEY_WRITE " . ($this->encrypted ? '1' : '0')) : null,
                $this->buildCharsetClause(),
                $this->buildCollateClause(),
                $this->buildCommentClause(),
                $this->buildEngineAttributeClause()
            ];
        }
    }
}

?>