<?php declare(strict_types = 1);

namespace Slate\Neat\Attribute {
    use Attribute;
    use Error;
    use Slate\Sql\SqlColumn;
    use Slate\Metalang\MetalangAttribute;
    use Slate\Neat\EntityDesign;
    use ReflectionProperty;
    use ReflectionUnionType;
    use RuntimeException;
    use Slate\Metalang\MetalangDesign;
    use Slate\Mvc\Env;
    use Slate\Sql\ISqlInferredType;
    use Slate\Sql\ISqlValueConvertable;
    use Slate\Sql\SqlTable;
    use Slate\Sql\Type\ISqlTypeForwardConvertable;
    use Slate\Sql\Type\SqlNativeTypeMap;
    use Slate\Sql\Type\SqlType;
    use Type;

    #[Attribute(Attribute::TARGET_PROPERTY)]
    class Column extends MetalangAttribute {    
        public ?SqlColumn $column = null;
    
        protected string  $columnName;

        protected bool   $validate = true;
    
        /**
         * Constructor for a column.
         *
         * @param string|null $name
         * @param string|null $type If null, it will automatically inferred by the property type (if present)
         * @param string|null $index Whether the column will be indexed and what type
         * @param bool|null   $incremental
         * @param bool|null   $nullable
         * 
         */
        public function __construct(string $name = null) {
            $parent = $this->parent;

            if($name === null)
                $name = $parent->getName();

            $propertyIsUnion = $parent->hasType() ? (\Cls::isSubclassInstanceOf($parent->getType(), ReflectionUnionType::class)) : false;

            if($propertyIsUnion)    
                throw new \Error(\Str::format(
                    "Column {}::\${} cannot have a union type.",
                    $parent->getDeclaringClass()->getName(),
                    $parent->getName()
                ));
            
            $this->columnName        = $name;
        }
    
        public function getColumnName(): string {
            return $this->columnName;
        }

        public function getColumn(string $entity): SqlColumn {
            $table = $entity::table();
            $column = $table->column($this->columnName);

            if(!$column)
                throw new RuntimeException(\Str::format(
                    "Unknown or undefined column {}::\${}.",
                    $this->parent->getDeclaringClass()->getName(),
                    $this->parent->getName()
                ));

            return $column;
        }

        public function hasDefault(): bool {
            return $this->parent->hasDefaultValue();
        }

        public function isIncremental() {
            if(!$this->parent->hasType())
                return false;

            $type = $this->parent->getType();

            return $type->getName() === "int" && $type->allowsNull();
        }

        public function requiresValidation(): bool {
            return false;
        }

        public function isForeignKey(): bool {
            return false;
        }
    }
}

?>