<?php

namespace Slate\Sql\Expression {

    use Slate\Data\IStringForwardConvertable;
    use Slate\Exception\SqlException;
    use Slate\Sql\Clause\TSqlEngineAttributeClause;
    use Slate\Sql\Clause\TSqlCollateClause;
    use Slate\Sql\Clause\TSqlCommentClause;
    use Slate\Sql\Clause\TSqlCharacterSetClause;
    use Slate\Sql\SqlConstruct;
    use Slate\Sql\SqlModifier;
    use Slate\Sql\TSqlModifierMiddleware;
    use Slate\Sql\TSqlModifiers;

class SqlColumnBlueprint extends SqlConstruct {
        use TSqlModifiers;
        use TSqlModifierMiddleware;

        use TSqlCharacterSetClause;
        use TSqlCommentClause;
        use TSqlCollateClause;
        use TSqlEngineAttributeClause;

        public const MODIFIERS = SqlModifier::VISIBILITY;

        protected string $name;
        protected string $datatype;
        protected bool   $nullable = false;

        protected ?string   $default = null;
        
        protected bool   $incremental = false;

        protected bool   $uniqueKey = false;
        protected bool   $primaryKey = false;

        protected ?string $insertionPoint = null;

        public function first(): static {
            $this->insertionPoint = "FIRST";

            return $this;
        }

        public function after(string $column): static {
            $this->insertionPoint = "AFTER {$column}";

            return $this;
        }


        //FIXED | DYNAMIC | DEFAULT
        protected ?string $format = null;

        protected ?string $storage = null;

        public function __construct(string $name) {
            $this->name = $name;
        }

        public function default(string|int|float|object|null $value): static {
            if($value === null) {
                $value = "NULL";
            }
            else if(is_scalar($value)) {
                $value = \Str::wrap(\Str::val($value), "'");
            }
            else if(\Cls::implements($value, IStringForwardConvertable::class)) {
                $value = \Str::wrapc($value->toString(), "()");
            }
            else {
                throw new \Error("Object must implement IStringForwardConvertable.");
            }
            
            $this->default = $value;

            return $this;
        }

        public function fixed(): static {
            $this->format = "FIXED";

            return $this;
        }

        public function dynamic(): static {
            $this->format = "dynamic";

            return $this;
        }
        
        public function is(string $datatype): static {
            $this->datatype = $datatype;

            return $this;
        }

        public function nullable(): static {
            $this->nullable = true;

            return $this;
        }

        public function increments(): static {
            return $this->incremental();
        }

        public function incremental(): static {
            $this->incremental = true;

            return $this;
        }

        public function uniqueKey(): static {
            $this->uniqueKey = true;

            return $this;
        }

        public function primaryKey(): static {
            $this->primaryKey = true;

            return $this;
        }

        public function disk(): static {
            $this->storage = "DISK";

            return $this;
        }

        public function memory(): static {
            $this->storage = "MEMORY";

            return $this;
        }

        public function name(): string {
            return $this->name;
        }

        public function build(): array {
            return [
                $this->name,
                $this->datatype,
                ($this->nullable ? "NOT NULL" : null),
                ($this->default ? "DEFAULT {$this->default}" : null),
                $this->visibility,
                $this->incremental ? "AUTO_INCREMENT" : null,
                $this->primaryKey ? "PRIMARY KEY" : null,
                $this->uniqueKey ? "UNIQUE KEY" : null,
                $this->buildCommentClause(),
                $this->buildCollateClause(),
                $this->format ? "COLUMN_FORMAT {$this->format}" : null,
                $this->buildEngineAttributeClause(),
                $this->storage ? "STORAGE {$this->storage}" : null
            ];
        }

        
    }
}

?>