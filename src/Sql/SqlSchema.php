<?php

namespace Slate\Sql {
    use Slate\Facade\DB;
    use Slate\Sql\Statement\SqlDropStatement;

    final class SqlSchema implements ISqlStorageMedium {
        protected static array $schemas = [ ];

        protected string $name;
        protected string $charset = "DEFAULT";
        protected string $collation = "DEFAULT";

        protected array  $tables;

        protected function __construct(string $name) {
            $this->name = $name;
            $this->tables = [];
        }

        public static function named(string $name): static {
            $schema = &static::$schemas[$name];

            if($schema === null)
                $schema = new SqlSchema($name);

            return $schema;
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

        public function table(string $table): SqlTable {
            return (new SqlTable($this, $table));
        }
    }
}

?>