<?php

namespace Slate\Neat\Attribute {
    use Attribute;

    use Slate\Sql\SqlColumn;
    use Slate\Metalang\MetalangAttribute;
    use Slate\Neat\EntityDesign;
    use ReflectionProperty;
    use ReflectionUnionType;
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
        protected ?string $columnType;
        protected ?string $columnIndex;
        protected bool    $columnNullable;
        protected bool    $columnIncremental;
        protected bool    $columnPrimary;
        protected bool    $columnUnique;

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
        public function __construct(
            string $name = null,
            string $type = null,
            bool $incremental = null,
            string $index = null
        ) {
            $parent = $this->parent;

            $nullable = false;

            if($name === null)
                $name = $parent->getName();

            $propertyIsUnion = $parent->hasType() ? (\Cls::isSubclassInstanceOf($parent->getType(), ReflectionUnionType::class)) : false;

            if($propertyIsUnion)    
                throw new \Error(\Str::format(
                    "Column {}::\${} cannot have a union type.",
                    $parent->getDeclaringClass()->getName(),
                    $parent->getName()
                ));

            if($parent->hasType())
                $nullable = $parent->getType()->allowsNull();
            
            $this->columnName        = $name;
            $this->columnType        = $type;
            $this->columnNullable    = $nullable ?? false;
            $this->columnIncremental = $incremental ?? false;
            $this->columnIndex       = $index;
            $this->columnPrimary     = false;
            $this->columnUnique      = false;
        }
    
        public function getColumnName(): string {
            return $this->columnName;
        }

        public function getColumn(string $entity): SqlColumn {
            $table = $entity::table();

            if(!$table->has($this->columnName)) {
                $parent = $this->parent;
                $column = $table->column($this->columnName);
                $conn   = $table->conn();

                if($this->columnNullable)
                    $column->nullable();

                if($this->columnIncremental)
                    $column->incremental();

                if($this->columnIndex)
                    $column->index($this->columnIndex);

                if($this->columnPrimary)
                    $column->primary();

                if($this->columnUnique)
                    $column->unique();

                $type = $this->columnType;

                if($type === null) {
                    if(!$parent->hasType())
                        throw new \Error(\Str::format(
                            "Column {}::\${} cannot infer a sql type as the property itself has no type.",
                            $parent->getDeclaringClass()->getName(),
                            $parent->getName()
                        ));
    
                    $propertyType = $parent->getType()->getName();
                    $nativeMap = Env::get("orm.type.native-map") ?? SqlNativeTypeMap::MAP;
    
                    $type =
                        $nativeMap["*"][$propertyType]
                        ?? $nativeMap[$conn::NAME][$propertyType];
    
                    if(!$type) {
                        if(!class_exists($propertyType))
                            throw new \Error(\Str::format(
                                "Column {}::\${} specifies an unknown class '{}'.",
                                $parent->getDeclaringClass()->getName(),
                                $parent->getName(),
                                $propertyType
                            ));
    
                        if(!\Cls::implements($propertyType, ISqlInferredType::class))
                            throw new \Error(\Str::format(
                                "Column {}::\${}: to infer a type from '{}', it must implement ISqlInferredType.",
                                $parent->getDeclaringClass()->getName(),
                                $parent->getName(),
                                $propertyType
                            ));
    
                        $type = $propertyType::inferSqlType($conn::NAME);
                    }

                    if(!$type) {
                        throw new \Error("Unable to infer type for column {}::\${}.");
                    }
                }
                else {
                    $pseudoMap = Env::get("orm.type.psuedo-type") ?? [];
    
                    if(($pseudoType = $pseudoMap[$type]) !== null) {
                        $type = $pseudoType;
                    }
                }

                $column->is($type);
            }
            else {
                $column = $table->column($this->columnName);
            }

            return $column;
        }

        public function hasDefault(): bool {
            return $this->parent->hasDefaultValue();
        }

        public function getDefault(): mixed {
            if($intialiser = ($this->parent->getDeclaringClass()->getName())::design()->getAttrInstance(Initialiser::class, $this->parent->getName()))
                return $intialiser->parent->invoke(null);

            if($this->parent->hasDefaultValue()) 
                return $this->parent->getDefaultValue();

            $sqlType = $this->column->getType();

            if($this->column->hasDefault()) {
                $phpType = $sqlType->getScalarType();

                if($this->parent->hasType()) {
                    $phpType = $this->parent->getType()->getName();

                    if(!\class_exists($phpType)) {
                        if(($phpType = \Type::getByName($phpType)) === null) {
                            throw new \Error("Unknown type '{$phpType}'.");
                        }
                    }
                }

                return $this->column->getDefault($phpType);
            }

            // return
            //     $this->parent->hasDefaultValue()
            //     ? $this->parent->getDefaultValue()
            //     : (
            //         $this->getColumn()->hasDefault()
            //             ? \class_exists($type = ($this->parent->hasType() ? $this->parent->getType()->getName() : $this->column->getScalarType())) ? \Type::getByName()
            //             : false
            //     )
        }

        public function requiresValidation(): bool {
            return false;
        }

        public function isForeignKey(): bool {
            return false;
        }

        public function isIncremental(): bool {
            return $this->columnIncremental;
        }

        public function isNullable(): bool {
            return $this->columnNullable;
        }

        public function isGenerated(): bool {
            return false;
        }

        public function isUniqueKey(): bool {
            return $this->columnUnique;
        }

        public function isPrimaryKey(): bool {
            return $this->columnPrimary;
        }

        public function isKey(): bool {
            return $this->columnUnique || $this->columnPrimary;
        }
    }
}

?>