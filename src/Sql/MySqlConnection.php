<?php

namespace Slate\Sql {

use Slate\Facade\DB;

final class MySqlConnection extends SqlConnection {
        public const NAME   = "mysql";
        public const PREFIX = "mysql";

        public const TOKEN_IDENTIFIER_DELIMITER = '``';

        public function schematic(string $schema, string $table, bool $types = false): array {
            $map = [];

            $columnQuery = DB::select([
                "`schema`"      => "TABLE_SCHEMA",
                "`table`"       => "TABLE_NAME",
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
                    "refferrer_constraint" => "C.CONSTRAINT_NAME",

                    "referrer_schema" => "C.TABLE_SCHEMA",
                    "referrer_table" => "C.TABLE_NAME",
                    "referrer_column" => "KCU.COLUMN_NAME",

                    "referring_schema" => "KCU2.TABLE_SCHEMA",
                    "referring_table" => "C2.TABLE_NAME",
                    "referring_column" => "KCU2.COLUMN_NAME",

                    "checksum" => "MD5(
                        CONCAT(
                            C.CONSTRAINT_NAME,
                            C.TABLE_SCHEMA,
                            C.TABLE_NAME,
                            KCU.COLUMN_NAME,
                            KCU2.TABLE_SCHEMA,
                            C2.TABLE_NAME,
                            KCU2.COLUMN_NAME
                        )
                    )"
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
                ->innerJoin(
                    "INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS",
                    function($join) {
                        return $join
                            ->on("C.CONSTRAINT_SCHEMA", DB::raw("RC.CONSTRAINT_SCHEMA"))
                            ->on("C.CONSTRAINT_NAME", DB::raw("RC.CONSTRAINT_NAME"));
                    },
                    as: "RC"
                )
                ->innerJoin(
                    "INFORMATION_SCHEMA.TABLE_CONSTRAINTS",
                    function($join) {
                        return $join
                            ->on("RC.UNIQUE_CONSTRAINT_SCHEMA", DB::raw("C2.CONSTRAINT_SCHEMA"))
                            ->on("RC.UNIQUE_CONSTRAINT_NAME",   DB::raw("C2.CONSTRAINT_NAME"));
                    },
                    as: "C2"
                )
                ->innerJoin(
                    "INFORMATION_SCHEMA.KEY_COLUMN_USAGE",
                    function($join) {
                        return $join
                            ->on("C2.CONSTRAINT_SCHEMA", DB::raw("KCU2.CONSTRAINT_SCHEMA"))
                            ->on("C2.CONSTRAINT_NAME",   DB::raw("KCU2.CONSTRAINT_NAME"))
                            ->on("KCU.ORDINAL_POSITION", DB::raw("KCU2.ORDINAL_POSITION"));
                    },
                    as: "KCU2"
                )
            ->where("C.TABLE_SCHEMA", "=", $schema)
            ->where("C.TABLE_NAME", "=", $table);

            foreach($this->soloquery($constraintQuery->toString()) as $row) {
                $checksum = $row["checksum"];
                unset($row["checksum"]);

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
            }

            if($types)
                $map = \Arr::map(
                    $map,
                    function($column) {
                        $inst = new SqlColumn(MySqlConnection::PREFIX);
                        $inst->fromArray($column);
    
                        return $inst;
                    }
                );

            return $map;
        }
    }
}

?>