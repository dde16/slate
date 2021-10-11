<?php

namespace Slate\Sql {
    use Slate\Facade\DB;
    use Slate\Sql\Expression\SqlColumnBlueprint;

    final class SqlTable implements ISqlStorageMedium {
        protected SqlSchema $schema;
        protected string    $name;
        protected array     $columns;

        public function __construct(SqlSchema $schema, string $name) {
            $this->schema = $schema;
            $this->name   = $name;
        }

        public function column(string $name): SqlColumn|SqlColumnBlueprint|null {
            $column = &$this->columns[$name];

            if($column === null)
                $column = new SqlColumnBlueprint($name);

            return $column;
        }
        
        public function load(array $options = []): void { }

        public function name(): string {
            return $this->name;
        }

        public function fullname(): string {
            return "{$this->schema->name()}.{$this->name}";
        }

        public function exists(): bool {
            return DB::select()
                ->from("information_schema.TABLES")
                ->where("SCHEMA_NAME", $this->name)
                ->where("TABLE_NAME", $this->schema->name())
                ->exists();
        }

        public function drop(): void {}

        public function commit(): void {
            if($this->exists()) {

            }
            else {
                $this->create();
            }
        }

        public function lock(): void {}

        public function locked(): bool {return true;}

        public function unlock(): void {}

        public function create(): void {
            $stmt = DB::create()->table($this->fullname());

            foreach($this->columns as $column) {
                if(\Cls::isSubclassInstanceOf($column, SqlColumnBlueprint::class)) {
                    $stmt->column($column);
                }
            }

            debug($stmt->toString());
        }
    }
}

?>