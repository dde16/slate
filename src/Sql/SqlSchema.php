<?php

namespace Slate\Sql {

    use Closure;
    use Slate\Facade\DB;
    use Slate\Sql\Condition\SqlCondition;
    use Slate\Sql\Statement\SqlDropStatement;

    final class SqlSchema implements ISqlStorageMedium {

        protected string $name;
        protected string $charset = "DEFAULT";
        protected string $collation = "DEFAULT";

        protected array  $tables;

        public function __construct(SqlConnection $conn, string $name) {
            $this->name = $name;
            $this->conn = $conn;
            $this->tables = [];
        }

        public function conn(): SqlConnection {
            return $this->conn;
        }

        public function load(array $options = []): void { }

        public function name(): string {
            return $this->name;
        }

        public function exists(): bool {
            return DB::select()->from("information_schema.SCHEMATA")->where("SCHEMA_NAME", $this->name)->exists();
        }

        public function drop(): void {
            DB::drop()->schema($this->name)->go();
        }

        /**
         * Will create the schema if it doesn't  already exist.
         * 
         * @return void
         */
        public function create(): void {
            DB::create()
                ->schema($this->name)
                ->ifNotExists()
                ->charset($this->charset)
                ->collate($this->collation)
                ->go();
        }

        /**
         * Commit the schema.
         */
        public function commit(): void {
            DB::alter()
                ->schema($this->name)
                ->charset($this->charset)
                ->collate($this->collation)
                ->go();
        }

        /**
         * Get the hash of the schema as the current object.
         */
        public function hash(): string {
            return crc32("{$this->charset}{$this->collation}{$this->path}{$this->comment}");
        }

        public function table(string $name, Closure $callback = null): SqlTable {
            $table = &$this->tables[$name];

            if($table === null)
                $table = new SqlTable($this, $name);

            if($callback !== null)
                $callback($table);

            return $table;
        }
    }
}

?>