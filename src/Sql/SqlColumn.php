<?php

namespace Slate\Sql {

    use Slate\Facade\DB;
    use Slate\Sql\Clause\TSqlCharacterSetClause;
    use Slate\Sql\Clause\TSqlCollateClause;
    use Slate\Sql\Clause\TSqlCommentClause;
    use Slate\Sql\Clause\TSqlEngineAttributeClause;
    use Slate\Sql\Constraint\SqlForeignKeyConstraint;
    use Slate\Sql\Constraint\SqlPrimaryKeyConstraint;
    use Slate\Sql\Constraint\SqlUniqueConstraint;
    use Slate\Sql\Expression\SqlColumnBlueprint;
    use Slate\Sql\Expression\SqlConstraintBlueprint;
    use Slate\Sql\Type\SqlCharacterType;
    use Slate\Sql\Type\SqlDateTimeType;
    use Slate\Sql\Type\SqlNumericType;
    use Slate\Sql\Type\SqlTypeFactory;
    use Slate\Sql\Type\SqlType;

    class SqlColumn extends SqlConstruct {
        use TSqlModifiers;
        use TSqlModifierMiddleware;

        use TSqlCommentClause;
        use TSqlEngineAttributeClause;

        public const MODIFIERS = SqlModifier::VISIBILITY;


        protected string $name;

        protected bool   $nullable = false;
        protected bool   $primaryKey = false;
        protected bool   $uniqueKey = false;
        protected bool   $incremental = false;
        protected bool   $generated = false;

        public SqlType $type;

        protected ?int    $autoIncrement = null;

        public mixed $default = null;

        //TODO: remove
        protected bool    $foreignKey     = false;
        protected ?string $foreignSchema  = null;
        protected ?string $foreignTable   = null;
        protected ?string $foreignColumn  = null;

        protected ?SqlTable $table = null;

        public ?SqlForeignKeyConstraint $foreignKeyConstraint = null;
        public ?SqlIndex $index = null;

        public bool $buildIgnoreKeys = false;

        public function __construct(?SqlTable $table = null) {
            $this->table  = $table;
        }

        public function references(string $foreignSchema, string $foreignTable, string $foreignColumn): static {
            $this->foreignColumn = $foreignColumn;
            $this->foreignSchema = $foreignSchema;
            $this->foreignTable = $foreignTable;

            return $this;
        }

        public function index(string $type = null): SqlIndex {
            $this->index = new SqlIndex($this, "{$this->table->schema()->name()}_{$this->table->name()}_{$this->name}_IDX", $type);

            return $this->index;
        }

        public function getName(): string {
            return $this->name;
        }

        public function setName(string $name): void {
            $this->name = $name;
        }

        public function conn(): SqlConnection {
            return $this->table->conn();
        }

        public function table(): SqlTable {
            return $this->table;
        }

        public function fullname(): string {
            return $this->table->fullname().".".$this->conn()->wrap($this->name);
        }

        /**
         * Type getter.
         *
         * @return SqlType
         */
        public function getType(): SqlType {
            return $this->type;
        }

        /**
         * Type setter.
         *
         * @see SqlTypeFactory
         * @param string $datatype 
         *
         * @return SqlType
         */
        public function is(string|SqlType $datatype): static {
            if(is_string($datatype)) {
                $datatype = \Str::lower($datatype);

                $this->type = SqlType::fromString($this, $datatype);
            }
            else {
                $this->type = $datatype;
            }

            return $this;
        }

        /** Foreign Key */
        public function isForeignKey(): bool {
            return $this->foreignKey;
        }
        
        public function getForeignSchema(): string|null {
            return $this->foreignSchema;
        }

        public function getForeignTable(): string|null {
            return $this->foreignTable;
        }

        public function getForeignColumn(): string|null {
            return $this->foreignColumn;
        }

        public function isGenerated(): bool {
            return $this->generated;
        }

        public function isKey(): bool {
            return $this->isPrimaryKey() || $this->isUniqueKey();
        }

        /**
         * Primary key getter.
         *
         * @return boolean
         */
        public function isPrimaryKey(): bool {
            return $this->primaryKey;
        }

        /**
         * Primary key setter.
         *
         * @return static
         */
        public function primary(): static {
            $this->primaryKey = true;

            return $this;
        }

        /**
         * Unique key getter.
         *
         * @return boolean
         */
        public function isUniqueKey(): bool {
            return $this->uniqueKey;
        }

        /**
         * Unique key setter.
         *
         * @return static
         */
        public function unique(): static {
            $this->uniqueKey = true;

            return $this;
        }

        /**
         * Incremental getter.
         *
         * @return boolean
         */
        public function isIncremental(): bool {
            return $this->incremental;
        }

        /**
         * Incremental setter.
         *
         * @return static
         */
        public function increments(): static {
            $this->incremental = true;

            return $this;
        }

        /**
         * Incremental setter.
         *
         * @return static
         */
        public function incremental(): static {
            $this->incremental = true;

            return $this;
        }

        /**
         * Auto Increment value getter.
         *
         * @return integer|null
         */
        public function getAutoIncrement(): int {
            if(!$this->incremental)
                throw new \Error("This column is not incremental.");

            return
                DB::select([
                    $this->conn()->wrap("AUTO_INCREMENT")
                ])
                ->from($this->conn()->wrap("information_schema", "TABLES"))
                ->where("TABLE_SCHEMA", $this->table()->schema()->name())
                ->where("TABLE_NAME", $this->table()->name())
                ->pluck("AUTO_INCREMENT")
                ->current();
        }

        /**
         * Nullable getter.
         *
         * @return boolean
         */
        public function isNullable(): bool {
            return $this->nullable;
        }
        
        /**
         * Nullable setter.
         *
         * @return static
         */
        public function nullable(): static {
            $this->nullable = true;

            return $this;
        }

        /** Setters */
        public function default(string|int|float|object|null $value): static {
            if($value === null) {
                $value = "NULL";
            }
            else if(is_scalar($value)) {
                $value = \Str::wrap(\Str::val($value), "'");
            }
            else if(is_object($value)) {
                if(!\Cls::implements($value, IStringForwardConvertable::class))
                    throw new \Error("Object must implement IStringForwardConvertable.");

                $value = \Str::wrapc($value->toString(), "()");
            }
            
            $this->default = $value;

            return $this;
        }

        /**
         * Format fixed setter.
         *
         * @return static
         */
        public function fixed(): static {
            $this->format = "FIXED";

            return $this;
        }

        public function dynamic(): static {
            $this->format = "DYNAMIC";

            return $this;
        }

        public function name(): string {
            return $this->getName();
        }
        
        public function hasDefault(): bool {
            return $this->default !== null;
        }

        public function getDefault(string $target): mixed {
            return
                ($this->default !== "NULL" && $this->default !== null)
                    ? (
                        \Cls::implements($this, ISqlTypeBackwardConvertable::class)
                            ? $this->type->fromSqlValue($this->default, $target)
                            : $this->default
                    )
                    : null;
        }

        public function fromArray(array $array): void {
            $this->name = $array["name"];

            $this->nullable      = $array["nullable"] === "YES";

            $this->primaryKey    = $array["key"] === "PRI";
            $this->uniqueKey     = $array["key"] === "UNI";
            $this->incremental   = $array["extra"] === "auto_increment";
            $this->generated     = $array["extra"] === "VIRTUAL GENERATED";

            $this->autoIncrement = $array["autoIncrement"];

            $this->type =  SqlTypeFactory::create($this->table->conn()::NAME.".".$array["datatype"], [$this]);
            $this->type->fromArray($array);

            if(@$array["foreignKey"] !== null) {
                $this->foreignKey = true;
                list($this->foreignSchema, $this->foreignTable, $this->foreignColumn) = $array["foreignKey"];
            }

            $this->charset   = $array["charset"];
            $this->collation = $array["collation"];
        }

        public function build(): array {
            return [
                $this->table->conn()->wrap($this->name),
                $this->type->toString(),
                (!$this->nullable ? "NOT NULL" : null),
                ($this->default ? "DEFAULT {$this->default}" : null),
                $this->visibility,
                $this->incremental ? "AUTO_INCREMENT" : null,
                (!$this->buildIgnoreKeys && $this->primaryKey) ? "PRIMARY KEY" : null,
                (!$this->buildIgnoreKeys && $this->uniqueKey) ? "UNIQUE KEY" : null,
                // ...(\Cls::isSubclassInstanceOf($this->type, SqlCharacterType::class)
                //     ? [
                //         $this->buildCommentClause(),
                //         $this->buildCollateClause(),
                //     ]
                //     : []
                // ),
                $this->format ? "COLUMN_FORMAT {$this->format}" : null,
                $this->buildEngineAttributeClause(),
                $this->storage ? "STORAGE {$this->storage}" : null
            ];
        }

    }
}

?>