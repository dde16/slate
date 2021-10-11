<?php

namespace Slate\Sql\Statement {

    use Slate\Sql\Clause\TSqlCharacterSetClause;
    use Slate\Sql\Clause\TSqlCollateClause;
    use Slate\Sql\Clause\TSqlCommentClause;
    use Slate\Sql\Clause\TSqlCompressionClause;
    use Slate\Sql\Clause\TSqlEngineAttributeClause;
    use Slate\Sql\Clause\TSqlLikeClause;
    use Slate\Sql\Clause\TSqlMediumClause;
    use Slate\Sql\Expression\SqlColumnBlueprint;
    use Slate\Sql\Expression\SqlConstraintBlueprint;
    
    
    
    
    
    
    
    use Slate\Sql\SqlModifier;
    use Slate\Sql\SqlStatement;

    class SqlCreateTableStatement extends SqlStatement {
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

        protected string $name;

        protected array  $columns = [];
        protected array  $constraints = [];


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

        public function __construct(string $name) {
            $this->name = $name;
        }

        public function constraint(SqlConstraintBlueprint $constraint = null): SqlConstraintBlueprint {
            if($constraint === null)
                $constraint = new SqlConstraintBlueprint();

            $this->constraints[] = $constraint;

            return $constraint;
        }

        public function column(string|SqlColumnBlueprint $name = null): SqlColumnBlueprint {
            $column = is_string($name) ? new SqlColumnBlueprint($name) : $name;

            $this->columns[$column->name()] = $column;

            return $column;
        }

        public function buildColumns(): ?string {
            return
                !\Arr::isEmpty($this->columns)
                    ? \Arr::list(
                        \Arr::map(
                            $this->columns,
                            fn($column) => $column->toString()
                        ),
                        ",",
                        "",
                        "()"
                    )
                    : null
                ;
        }

        public function build(): array {
            return [
                "CREATE",
                $this->buildModifier(SqlModifier::TEMPORARY),
                "TABLE",
                $this->buildModifier(SqlModifier::IF_NOT_EXISTS),
                $this->name,
                $this->buildColumns(),
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