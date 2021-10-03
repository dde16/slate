<?php

namespace Slate\Sql\Expression {

    use Slate\Sql\Clause\TSqlCommentClause;
    use Slate\Sql\Clause\TSqlEngineAttributeClause;
    use Slate\Sql\Clause\TSqlReferencesClause;
    use Slate\Sql\Clause\TSqlWithParserClause;
    use Slate\Sql\Modifier\TSqlCascadeModifier;
    use Slate\Sql\Modifier\TSqlNoActionModifier;
    use Slate\Sql\Modifier\TSqlRestrictModifier;
    use Slate\Sql\Modifier\TSqlSetDefaultModifier;
    use Slate\Sql\Modifier\TSqlSetNullModifier;
    use Slate\Sql\Modifier\TSqlVisibilityModifiers;
    use Slate\Sql\SqlConstruct;

    class SqlConstraintBlueprint extends SqlConstruct {
        use TSqlEngineAttributeClause;
        use TSqlVisibilityModifiers;
        use TSqlWithParserClause;
        use TSqlCommentClause;

        use TSqlReferencesClause;

        protected ?string $symbol = null;

        protected bool   $standalone = false;

        protected string $type;
        protected ?string $typeModifier = null;

        protected ?string $indexName = null;
        protected ?string $indexType = null;

        protected ?string $keyPart = null;

        protected ?string $ref = null;

        public function btree(): static {
            $this->indexType = "BTREE";

            return $this;
        }

        public function hash(): static {
            $this->indexType = "HASH";

            return $this;
        }

        public function fulltext(): static {
            $this->type = "FULLTEXT";
            $this->standalone = true;

            return $this;
        }

        public function spatial(): static {
            $this->type = "FULLTEXT";
            $this->standalone = true;

            return $this;
        }

        public function uniqueKey(): static {
            $this->type = "UNIQUE";

            return $this->key();
        }

        public function uniqueIndex(): static {
            $this->type = "UNIQUE";

            return $this->index();
        }

        public function primaryKey(): static {
            $this->type = "PRIMARY";

            return $this->key();
        }

        public function foreignKey(): static {
            $this->type = "FOREIGN";

            return $this->key();
        }

        public function key(): static {
            $this->typeModifier = "KEY";

            return $this;
        }

        public function index(): static {
            $this->typeModifier = "INDEX";

            return $this;
        }

        public function build(): array {
            $build = [];

            if($this->standalone) {
                $build[] = $this->type;
                $build[] = $this->typeModifier;
            }
            else if($this->type === null) {
                $build[] = $this->typeModifier;
            }
            else {
                if($this->symbol) {
                    $build[] = "CONSTRAINT";
                    $build[] = $this->symbol;
                }

                $build[] = $this->type;
                $build[] = $this->typeModifier;
            }

            $build[] = $this->indexName;
            $build[] =
                $this->indexType
                    ? "USING {$this->indexType}"
                    : null
                ;

            $build[] = $this->buildReferencesClause();
            $build[] = $this->buildCommentClause();
            $build[] = $this->buildEngineAttributeClause();
            $build[] = $this->buildWithParserClause();


            return $build;
        }
    }

}

?>
