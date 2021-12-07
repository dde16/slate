<?php

namespace Slate\Sql\Type {

    use Slate\Sql\SqlColumn;
    use Slate\Sql\SqlConstruct;
    use Slate\Sql\SqlTable;

    abstract class SqlType {
        /**
         * Name of the sql type.
         *
         * @var string
         */
        protected string $datatype;

        /**
         * Native PHP size in bytes.
         *
         * @var integer
         */
        protected int    $size;

        protected $column;

        public function __construct($column) {
            $this->column = $column;
        }

        public function getDataType(): string {
            return $this->datatype;
        }

        public function fromArray(array $array): void {
            $this->datatype = $array["datatype"];
        }

        /**
         * Get the (maximum) size of the current type in native types.
         */
        public function getSize(): int {
            return $this->size;
        }


        /**
         * Get the native type recommendation for this type.
         * 
         * For example, BIGINT will exceed the native integer capacity.
         * So it should be stored as a string.
         */
        public abstract function getScalarType(): string;
        
        public abstract function build(): array;
    
        public function toString(): string {
            return \Arr::join(\Arr::filter($this->build()), " ");
        }

        public static function fromString(SqlColumn $column, string $test): static {
            $matches = [];

            if(!preg_match("/^(?'name'[a-zA-Z_]+)(?:\((?'arg0'\d+)(?:,(?'arg1'\d+))?\))?(?:\ *(?'sign'(?:un)?signed))?$/i", $test, $matches, PREG_UNMATCHED_AS_NULL))
                throw new \Error("Invalid sql type string '{$test}'.");

            $type = SqlTypeFactory::create($column->conn()::NAME.".{$matches['name']}", [$column]);
            $type->fromArray(["datatype" => $matches["name"]]);

            if(\Str::lower($matches["sign"] ?? "") === "unsigned" && \Cls::isSubclassInstanceOf($type, SqlNumericType::class))
                $type->unsigned();

            if(\Cls::isSubclassInstanceOf($type, SqlCharacterType::class) && !\Cls::isSubclassInstanceOf($type, SqlCharacterTextType::class)) {
                if($matches["arg0"] === null)
                    throw new \Error("Character type '{$type->getDataType()}' must specify a length.");

                $type->len(\Integer::tryparse($matches["arg0"]));
                
            }
            else if(\Cls::isSubclassInstanceOf($type, SqlNumericType::class) && $matches["arg0"] !== null) {
                $type->precision(\Integer::tryparse($matches["arg0"]));
            }

            if($matches["arg1"] !== null)
                $type->scale(\Integer::tryparse($matches["arg1"]));

            return $type;
        }
    }
}

?>