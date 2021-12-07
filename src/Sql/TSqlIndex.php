<?php

namespace Slate\Sql {

    use Slate\Sql\Clause\TSqlCommentClause;
    use Slate\Sql\Clause\TSqlEngineAttributeClause;
    use Slate\Sql\Clause\TSqlWithParserClause;

    trait TSqlIndex {
        use TSqlModifiers;
        use TSqlModifierMiddleware;
        
        use TSqlEngineAttributeClause;
        use TSqlWithParserClause;
        use TSqlCommentClause;

        protected ?string $indexName = null;
        protected ?string $indexType = null;
        
        protected SqlColumn $column;

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

        public function build(): array {
            return [
                $this->synonym,
                $this->column->conn()->wrap($this->indexName),
                (\Str::wrapc($this->column->conn()->wrap($this->column->getName()), "()")),
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