<?php declare(strict_types = 1);

namespace Slate\Sql\Trait {

    use Slate\Sql\Clause\TSqlCommentClause;
    use Slate\Sql\Clause\TSqlEngineAttributeClause;
    use Slate\Sql\Clause\TSqlWithParserClause;
    use Slate\Sql\SqlModifier;
    use Slate\Sql\Trait\TSqlModifierMiddleware;
    use Slate\Sql\Trait\TSqlModifiers;

    trait TSqlIndex {
        use TSqlModifiers;
        use TSqlModifierMiddleware;
        
        use TSqlEngineAttributeClause;
        use TSqlWithParserClause;
        use TSqlCommentClause;

        protected ?string $indexName = null;
        protected ?string $indexType = null;

        public function btree(): static {
            $this->type = "BTREE";

            return $this;
        }

        public function hash(): static {
            $this->type = "HASH";

            return $this;
        }

        public function getIndexName(): string {
            return $this->indexName;
        }

        public function buildSql(): array {
            return [
                $this->synonym,
                ($this->indexName ? $this->table->conn()->wrap($this->indexName) : null),
                (\Str::wrapc($this->table->conn()->wrap($this->column), "()")),
                ($this->indexType ? "USING {$this->indexType}" : null),
                $this->buildModifier(SqlModifier::VISIBILITY),
                $this->buildEngineAttributeClause(),
                $this->buildWithParserClause(),
                $this->buildCommentClause()
            ];
        }
    }
}

?>