<?php declare(strict_types = 1);

namespace Slate\Sql\Connection {
    use Error;
    use Slate\Facade\DB;
    use Slate\Sql\SqlConnection;
    use Slate\Sql\SqlIndex;
    use Slate\Sql\Statement\SqlSelectStatement;

    final class MySqlConnection extends SqlConnection {
        public const NAME   = "mysql";
        public const PREFIX = "mysql";
        public const IDENTIFIER = '``';

        public function schematic(string $schema, string $table, bool $types = false): array {
            throw new Error();
            $map = [];

            $columnQuery = DB::select([
                "`schema`"      => "TABLE_SCHEMA",
                "`table`"       => "TABLE_NAME",
                "`position`"    => "ORDINAL_POSITION",
                "`name`"        => "COLUMN_NAME",
                "`nullable`"    => "IS_NULLABLE",
                "`default`"     => "COLUMN_DEFAULT",
                "`datatype`"    => "DATA_TYPE",
                "`charLength`"  => "CHARACTER_MAXIMUM_LENGTH",
                "`octetLength`" => "CHARACTER_OCTET_LENGTH",
                "`precision`"   => "IF(ISNULL(NUMERIC_PRECISION), DATETIME_PRECISION, NUMERIC_PRECISION)",
                "`scale`"       => "NUMERIC_SCALE",
                "`charset`"     => "CHARACTER_SET_NAME",
                "`collation`"   => "COLLATION_NAME",
                "`key`"         => "COLUMN_KEY",
                "`extra`"       => "EXTRA"
            ])
            ->from("information_schema.COLUMNS")
            ->where("`TABLE_SCHEMA`", "=", $schema)
            ->where("`TABLE_NAME`", "=", $table);

            foreach($this->soloquery($columnQuery->toString()) as $row) {
                $row["autoIncrement"] = null;

                $map[$row["name"]] = $row;
            }

            $constraintQuery =
                DB::select([
                    "referrer_constraint" => "C.CONSTRAINT_NAME",

                    "referrer_schema" => "C.TABLE_SCHEMA",
                    "referrer_table" => "C.TABLE_NAME",
                    "referrer_column" => "KCU.COLUMN_NAME",

                    "referring_schema" => "KCU.REFERENCED_TABLE_SCHEMA",
                    "referring_table" => "KCU.REFERENCED_TABLE_NAME",
                    "referring_column" => "KCU.REFERENCED_COLUMN_NAME"

                ])->from("INFORMATION_SCHEMA.TABLE_CONSTRAINTS", as: "C")
                ->innerJoin(
                    "INFORMATION_SCHEMA.KEY_COLUMN_USAGE",
                    function($join) {
                        return $join
                            ->on("C.CONSTRAINT_SCHEMA", DB::raw("KCU.CONSTRAINT_SCHEMA"))
                            ->on("C.CONSTRAINT_TYPE", "FOREIGN KEY")
                            ->on("C.CONSTRAINT_NAME", DB::raw("KCU.CONSTRAINT_NAME"));
                    },
                    as: "KCU"
                )
                ->where("C.TABLE_SCHEMA", "=", $schema)
                ->where("C.TABLE_NAME", "=", $table);

            foreach($this->soloquery($constraintQuery->toString()) as $row) {
                $map[$row["referrer_column"]]["foreignKey"]   = [$row["referring_schema"], $row["referring_table"], $row["referring_column"]];
            }

            $autoIncrementQuery = DB::select([ "`auto_increment`"      => "AUTO_INCREMENT" ])
                ->from("information_schema.TABLES")
                ->where("`TABLE_SCHEMA`", "=", $schema)
                ->where("`TABLE_NAME`",   "=", $table);

            foreach($map as &$column) {
                if($column["extra"] === "auto_increment") {
                    $column["autoIncrement"] = (@$autoIncrementQuery->first())["auto_increment"];
                }
                else if(empty($column["extra"])) {
                    $column["extra"] = null;
                }

                $column["nullable"] = $column["nullable"] === "YES";
            }

            if($types)
                throw new Error("Deprecated");

            return $map;
        }
    }
}

?>