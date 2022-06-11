<?php declare(strict_types = 1);

namespace Slate\Sql {

    use Slate\Sql\Medium\SqlTable;
    use Slate\Sql\Trait\TSqlIndex;
    use Slate\Sql\Trait\TSqlUsingConnection;

    abstract class SqlConstraint extends SqlConstruct {
        use TSqlUsingConnection;

        use TSqlIndex {
            TSqlIndex::buildSql as buildIndex;
        }

        public const MODIFIERS = SqlModifier::VISIBILITY;

        public string $synonym = "KEY";

        public ?string $symbol = null;

        protected SqlTable $table;

        public function __construct(SqlTable $table, ?string $symbol = null) {
            $this->table = $table;
            $this->symbol = $symbol;
        }

        public function getSymbol(): string {
            return $this->symbol ?? \Arr::join([$this->table->schema()->name(), $this->table->name(), ...$this->columns, static::SYMBOL_SHORTHAND], "_");
        }

        public function fromArray(array $array): void {
            $this->symbol = $array["name"];
        }

        public function commit(): void {  
            $this
                ->table
                ->conn()
                ->alterTable($this->table->schema()->name(), $this->table->name())
                ->add($this)
                ->go();
        }

        public function drop(): void {  
            $this
                ->table
                ->conn()
                ->alterTable($this->table->schema()->name(), $this->table->name())
                ->dropConstraint($this->getSymbol())
                ->go();
        }
    }
}

?>
