<?php declare(strict_types = 1);

namespace Slate\Sql\Statement {
    use Slate\Sql\SqlModifier;
    use Slate\Sql\SqlStatement;

    class SqlDropStatement extends SqlStatement {
        public const MODIFIERS =
            SqlModifier::RESTRICT
            | SqlModifier::CASCADE
            | SqlModifier::TEMPORARY
            | SqlModifier::IF_EXISTS
        ;

        public function table(string $ref, ?string $subref = null): SqlDropTableStatement {
            return (new SqlDropTableStatement($this->conn, $ref, $subref));
        }

        public function schema(string $ref): SqlDropSchemaStatement {
            return (new SqlDropSchemaStatement($this->conn, $ref));
        }

        public function buildSql(): array {
            return [
                "DROP",
                $this->buildModifier(SqlModifier::RESTRICT),
                $this->type,
                $this->name,
                $this->buildModifier(SqlModifier::IF_EXISTS),
                $this->buildModifier(SqlModifier::RESTRICT)
                    ?: $this->buildModifier(SqlModifier::CASCADE)
            ];
        }
    }
}

?>