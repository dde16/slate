<?php declare(strict_types = 1);

namespace Slate\Sql\Statement {

    use Slate\Sql\Clause\TSqlCharacterSetClause;
    use Slate\Sql\Clause\TSqlCollateClause;
    use Slate\Sql\Clause\TSqlCommentClause;
    use Slate\Sql\Clause\TSqlCompressionClause;
    use Slate\Sql\Clause\TSqlEngineAttributeClause;
    use Slate\Sql\Clause\TSqlLikeClause;
    use Slate\Sql\Clause\TSqlMediumClause;
    use Slate\Sql\Expression\SqlConstraintBlueprint;
    use Slate\Sql\SqlColumn;
    use Slate\Sql\SqlConnection;
    use Slate\Sql\SqlModifier;
    use Slate\Sql\SqlStatement;
    use Slate\Sql\SqlConstraint;
    use Slate\Sql\SqlIndex;
    use Slate\Sql\Statement\Trait\TSqlTableStatement;

    class SqlCreateTableStatement extends SqlStatement {
        use TSqlTableStatement;

        use TSqlCharacterSetClause;
        use TSqlCollateClause;
        use TSqlCommentClause;
        use TSqlEngineAttributeClause;

        /** Table options */
        use TSqlCompressionClause;
        use TSqlLikeClause;

        public const MODIFIERS =
            SqlModifier::TEMPORARY
            | SqlModifier::IGNORE
            | SqlModifier::REPLACE
            | SqlModifier::IF_NOT_EXISTS
            | SqlModifier::DELAY_KEY_WRITE
            | SqlModifier::CHECKSUM
            | SqlModifier::ENCRYPTION
        ;


        protected array  $columns = [];
        protected array  $constraints = [];
        protected array  $indexes = [ ];

        protected ?int $autoExtendSize = null;
        protected ?int $autoIncrement = null;
        protected ?int $avgRowLength = null;

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

        public function constraint(SqlConstraint $constraint = null): SqlConstraint {
            if($constraint === null)
                $constraint = new SqlConstraint();

            $this->constraints[] = $constraint;

            return $constraint;
        }

        public function column(SqlColumn $column): static {
            $this->columns[$column->name()] = $column;

            return $this;
        }

        public function index(SqlIndex $index): static {
            $this->indexes[$index->getIndexName()] = $index;

            return $this;
        }

        public function buildColumns(): ?string {
            return
                !\Arr::isEmpty($this->columns)
                    ? \Arr::list(
                        \Arr::map(
                            $this->columns,
                            fn($column) => $column->toString()
                        ),
                        ","
                    )
                    : null
                ;
        }

        public function buildConstraints(): ?string {
            return
                !\Arr::isEmpty($this->constraints)
                    ? \Arr::list(
                        \Arr::map(
                            $this->constraints,
                            fn(SqlConstraint $constraint): string => $constraint->toString()
                        ),
                        ","
                    )
                    : null
                ;
        }

        public function buildIndexes(): ?string {
            return
                !\Arr::isEmpty($this->indexes)
                    ? \Arr::list(
                        \Arr::map(
                            $this->indexes,
                            fn(SqlIndex $index): string => $index->toString()
                        ),
                        ","
                    )
                    : null
                ;
        }

        public function buildSql(): array {
            $columns = $this->buildColumns();
            $indexes = $this->buildIndexes();
            $constraints = $this->buildConstraints();

            $definitions = [$columns, $indexes, $constraints];

            return [
                "CREATE",
                $this->buildModifier(SqlModifier::TEMPORARY),
                "TABLE",
                $this->buildModifier(SqlModifier::IF_NOT_EXISTS),
                $this->name,
                (\Arr::any($definitions)
                    ? \Arr::list($definitions, ", ", listWrap: "()")
                    : null
                ),
                $this->buildLikeClause(),
                ...$this->buildModifiers([
                    SqlModifier::CHECKSUM,
                    SqlModifier::ENCRYPTION
                ]),
                $this->buildCompressionClause(),
                $this->buildModifier(SqlModifier::DELAY_KEY_WRITE),
                $this->buildCharsetClause(),
                $this->buildCollateClause(),
                $this->buildCommentClause(),
                $this->buildEngineAttributeClause()
            ];
        }
    }
}

?>