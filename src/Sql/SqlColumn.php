<?php

namespace Slate\Sql {
    use Slate\Sql\Type\SqlTypeFactory;
    use Slate\Sql\Type\SqlType;

    class SqlColumn {
        // public string $schema;
        // public string $table;
        protected string $driver;
        protected string $name;

        protected bool   $nullable;
        protected bool   $primaryKey;
        protected bool   $uniqueKey;
        protected bool   $incremental;
        protected bool   $generated;

        protected SqlType $type;

        protected ?string $charset = null;
        protected ?string $collation = null;

        protected ?int    $autoIncrement = null;

        protected bool    $foreignKey     = false;
        protected ?string $foreignSchema  = null;
        protected ?string $foreignTable   = null;
        protected ?string $foreignColumn  = null;

        public function __construct(string $driver) {
            $this->driver = $driver;
        }

        public function getDriver(): string {
            return $this->driver;
        }

        public function getName(): string {
            return $this->name;
        }

        public function getType(): SqlType {
            return $this->type;
        }

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

        public function getCharset(): string {
            return $this->charset;
        }

        public function getCollation(): string {
            return $this->collation;
        }

        public function isGenerated(): bool {
            return $this->generated;
        }

        public function isKey(): bool {
            return $this->isPrimaryKey() || $this->isUniqueKey();
        }

        public function isPrimaryKey(): bool {
            return $this->primaryKey;
        }

        public function isUniqueKey(): bool {
            return $this->uniqueKey;
        }

        public function isIncremental(): bool {
            return $this->incremental;
        }

        public function isNullable(): bool {
            return $this->nullable;
        }

        public function getAutoIncrement(): int|null {
            return $this->autoIncrement;
        }


        public function fromArray(array $array): void {
            // $this->schema = $array["schema"];
            // $this->table = $array["table"];
            $this->name = $array["name"];

            $this->nullable      = $array["nullable"] === "YES";

            $this->primaryKey    = $array["key"] === "PRI";
            $this->uniqueKey     = $array["key"] === "UNI";
            $this->incremental   = $array["extra"] === "auto_increment";
            $this->generated     = $array["extra"] === "VIRTUAL GENERATED";


            $this->autoIncrement     = $array["autoIncrement"];

            $this->type =  SqlTypeFactory::create($this->driver.".".$array["datatype"]);
            $this->type->fromArray($array);

            if(@$array["foreignKey"] !== null) {
                $this->foreignKey = true;
                list($this->foreignSchema, $this->foreignTable, $this->foreignColumn) = $array["foreignKey"];
            }

            $this->charset   = $array["charset"];
            $this->collation = $array["collation"];
        }

        public function getDefault(string $target): mixed {
            return $this->type->getDefault($target);
        }
    }
}

?>