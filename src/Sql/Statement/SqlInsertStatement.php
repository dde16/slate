<?php

namespace Slate\Sql\Statement {
    use Slate\Sql\SqlConstruct;
    use Slate\Sql\SqlStatement;

    
    

    use Slate\Sql\Clause\TSqlIntoClause;
    use Slate\Sql\Clause\TSqlOnDuplicateKeyUpdateClause;

    use Slate\Facade\DB;
    use Slate\Facade\Sql;
    use Slate\Mvc\App;
    use Slate\Sql\ISqlResultProvider;

    use Slate\Sql\Expression\TSqlColumnsExpression;
    use Slate\Sql\SqlModifier;

class SqlInsertStatement extends SqlStatement  {
        use TSqlIntoClause;
        use TSqlOnDuplicateKeyUpdateClause;

        public array $columns = [];
        public array $rows    = [];

        public const MODIFIERS = SqlModifier::LOW_PRIORITY | SqlModifier::HIGH_PRIORITY | SqlModifier::IGNORE;
    
    
        public function build(): array {
            return [
                "INSERT",
                ($this->buildModifier(SqlModifier::LOW_PRIORITY)
                ?: $this->buildModifier(SqlModifier::HIGH_PRIORITY)),
                $this->buildModifier(SqlModifier::IGNORE),
                $this->buildIntoClause(),
                $this->buildColumns(),
                $this->buildValuesClause(),
                $this->buildOnDuplicateKeyUpdateClause()
            ];
        }

        public function buildColumns() {
            return !\Arr::isEmpty($this->columns) ? \Str::wrapc(\Arr::join(\Arr::map($this->columns, function($col) { return \Str::wrap($col, "`"); }), ", "), "()") : null;
        }

        
        public function buildValuesClause() {
            return !\Arr::isEmpty($this->rows)
                ? "VALUES " . \Arr::join(\Arr::map(
                    $this->rows,
                    function($row)  {
                        return \Str::wrapc(\Arr::join(\Arr::map(
                            \Arr::values(\Arr::rekey($row, $this->columns)),
                            [ Sql::class, "sqlify" ]
                        ), ", "), "()");
                    }
                ), ", ")
                : null;
        }

        public function row(array $row) {

            $this->columns(\Arr::keys($row));

            $this->rows[] = $row;

            return $this;
        }

        public function columns(array $columns) {
            $this->columns = array_merge(
                $this->columns,
                array_diff($columns, $this->columns)
            );

            return $this;
        }

        public function rows(array $rows) {
            foreach($rows as $row) $this->row($row);

            return $this;
        }

        public function go(): bool  {
            $conn = App::conn($this->conn);

            return $conn->prepare($this->toString())->execute();
        }
    }
}

?>