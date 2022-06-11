<?php declare(strict_types = 1);

namespace Slate\Sql\Statement {

    use Slate\Sql\Clause\TSqlDropAuxiliariesClause;
    use Slate\Sql\Clause\TSqlRenameAuxiliariesClause;
    use Slate\Sql\Clause\TSqlRenameClause;
    use Slate\Sql\SqlColumn;
    use Slate\Sql\SqlConnection;
    use Slate\Sql\SqlConstraint;
    use Slate\Sql\SqlIndex;
    use Slate\Sql\SqlModifier;
    use Slate\Sql\SqlStatement;

    use function PHPSTORM_META\map;

class SqlAlterTableStatement extends SqlStatement {
        use TSqlDropAuxiliariesClause;
        use TSqlRenameAuxiliariesClause;
        use TSqlRenameClause;

        public const MODIFIERS = SqlModifier::FORCE;

        protected array $additions = [];

        public array $modifyColumns = [];

        protected string $table;
        
        public function __construct(SqlConnection $conn, string $ref, ?string $subref = null) {
            parent::__construct($conn);

            $refs  = [$ref];

            if($subref !== null)
                $refs[] = $subref;

            $this->table = $conn->wrap(...$refs);
        }

        public function modify(SqlColumn $column): static {
            $this->modifyColumns[$column->getName()] = $column;

            return $this;
        }

        public function add(SqlColumn|SqlConstraint|SqlIndex $addition) {
            $this->additions[] = $addition;

            return $this;
        }

        public function buildAdditions(): ?string {
            return (
                !\Arr::isEmpty($this->additions)
                    ? \Arr::join(
                        \Arr::map(
                            \Arr::values($this->additions),
                            fn(SqlColumn|SqlConstraint|SqlIndex $addition): string => "ADD " . $addition->toSql()
                        ),
                        ", "
                    )
                    : null
            );
        }

        public function buildModifyColumns(): ?string {
            return (
                !\Arr::isEmpty($this->modifyColumns)
                    ? \Arr::join(
                        \Arr::map(
                            \Arr::values($this->modifyColumns),
                            fn(SqlColumn $col): string => "MODIFY COLUMN " . $col->toString()
                        ),
                        ", "
                    )
                    : null
            );
        }

        public function go(): bool {
            if(\Arr::all([
                $this->modifyColumns,
                $this->additions,
                $this->renames,
                $this->drops
            ], fn($array) => \Arr::isEmpty($array))) {
                return true;
            }

            return parent::go();
        }

        public function buildSql(): array {
            $additions = $this->buildAdditions();
            $modified = $this->buildModifyColumns();
            $drops = $this->buildDropAuxiliariesClause();

            return [
                "ALTER TABLE",
                $this->table,
                \Arr::join([$drops, $additions, $modified], ", "),
                $this->buildRenameClause(),
                $this->buildRenameAuxiliariesClause(),
                $this->buildModifier(SqlModifier::FORCE)
            ];
        }
    }
}

?>