<?php

namespace Slate\Sql\Statement {

    use Slate\Sql\Clause\TSqlDropAuxiliariesClause;
    use Slate\Sql\Clause\TSqlRenameAuxiliariesClause;
    use Slate\Sql\Clause\TSqlRenameClause;
    use Slate\Sql\SqlColumn;
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

        public function __construct(string $name) {
            $this->table = $name;
        }

        public function modify(SqlColumn $column): static {
            $this->modifyColumns[$column->getName()] = $column;

            return $this;
        }

        public function add(SqlColumn|SqlConstraint|SqlIndex $addition) {
            $type = null;

            if(\Cls::isSubclassInstanceOf($addition, SqlColumn::class)) {
                $type = "COLUMN";
            }
            else if(\Cls::isSubclassInstanceOf($addition, SqlConstraint::class)) {
                $type = "CONSTRAINT";
            }
            else if(\Cls::isSubclassInstanceOf($addition, SqlIndex::class)) {
                $type = "INDEX";
            }

            $this->additions[] = [$type, $addition];

            return $this;
        }

        public function buildAdditions(): ?string {
            return (
                !\Arr::isEmpty($this->additions)
                    ? \Arr::join(
                        \Arr::map(
                            \Arr::values($this->additions),
                            fn(array $entry): string => "ADD " . $entry[0] . " " . $entry[1]->toString()
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

        public function build(): array {
            $additions = $this->buildAdditions();
            $modified = $this->buildModifyColumns();

            return [
                "ALTER TABLE",
                $this->table,
                $this->buildDropAuxiliariesClause(),
                \Arr::join([$additions, $modified], ", "),
                $this->buildRenameClause(),
                $this->buildRenameAuxiliariesClause(),
                $this->buildModifier(SqlModifier::FORCE)
            ];
        }
    }
}

?>