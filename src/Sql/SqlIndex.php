<?php declare(strict_types = 1);

namespace Slate\Sql {

    use Slate\Sql\Trait\TSqlIndex;

    class SqlIndex extends SqlConstruct {
        use TSqlIndex {
            TSqlIndex::buildSql as buildIndex;
        }
        
        protected string $synonym = "INDEX";

        public const MODIFIERS = SqlModifier::VISIBILITY;

        public function __construct(SqlColumn $column, string $name, string $type = null) {
            $this->column  = $column;
            $this->indexName  = $name;
            $this->indexType = $type;
        }

        protected ?string $modifier = null;

        public function fulltext(): static {
            $this->modifier = "FULLTEXT";

            return $this;
        }

        public function spatial(): static {
            $this->modifier = "SPATIAL";

            return $this;
        }

        public function buildSql(): array {
            return [
                $this->modifier,
                ...$this->buildIndex()
            ];
        }
    }
}

?>