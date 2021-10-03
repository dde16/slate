<?php

namespace Slate\Sql {

use Slate\Facade\DB;

final class SqlTable implements ISqlStorageMedium {
        protected SqlSchema $schema;
        protected string    $name;
        protected array     $columns;

        public function __construct(SqlSchema $schema, string $name) {
            $this->schema = $schema;
            $this->name   = $name;
        }

        public function column(string $name): SqlColumn {
            return $this->columns[$name];
        }

        public function add(SqlColumn $column) { }

        public function exists(): bool {
            return DB::select()
                ->from("information_schema.TABLES")
                ->where("SCHEMA_NAME", $this->name)
                ->where("TABLE_NAME", $this->schema->name())
                ->exists();
        }

        public function drop(): void {}

        public function commit(): void {}

        public function lock(): void {}

        public function locked(): bool {return true;}

        public function unlock(): void {}

        public function create(): void {
            $stmt = DB::create()->table("{$this->schema->name()}.{$this->name}");

            
        }
    }
}

?>