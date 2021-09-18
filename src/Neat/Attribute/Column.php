<?php

namespace Slate\Neat\Attribute {
    use Attribute;

    use Slate\Sql\SqlColumn;
    use Slate\Metalang\MetalangAttribute;
    use Slate\Neat\EntityDesign;
    use ReflectionProperty;
    use ReflectionUnionType;

#[Attribute(Attribute::TARGET_PROPERTY)]
    class Column extends MetalangAttribute {

        public const NAME = "Column";
    
        public ?SqlColumn $column = null;
    
        protected ?string $columnName = null;

        protected ?string $generator = null;

        protected bool   $validate;
    
        public function __construct(string $name = null, string $generator = null, bool $validate = true) {
            $this->columnName = $name;
            $this->validate   = $validate;
            $this->generator   = $generator;
        }

        public function consume($property): void {
            parent::consume($property);

            if($this->columnName === null)
                $this->columnName = $property->getName();

            $propertyIsUnion = $property->hasType() ? (\Cls::isSubclassInstanceOf($property->getType(), ReflectionUnionType::class)) : false;

            if($propertyIsUnion)    
                throw new \Error(\Str::format(
                    "Column property {}::\${} cannot have a union type.",
                    $property->getDeclaringClass()->getName(),
                    $property->getName()
                ));

            // if($property->isPublic())
            //     throw new \Error(\Str::format(
            //         "Column {}::\${} must be protected.",
            //         $property->getDeclaringClass()->getName(),
            //         $property->getName()
            //     ));
        }
    
        public function getColumnName(): string {
            return $this->columnName ?: $this->column->getName();
        }

        public function getColumn(): SqlColumn {
            return $this->column;
        }

        public function hasDefault(): bool {
            return $this->parent->hasDefaultValue() || $this->column->getType()->hasDefault();
        }

        public function getDefault(): mixed {
            if($intialiser = ($this->parent->getDeclaringClass()->getName())::design()->getAttrInstance(Initialiser::class, $this->parent->getName()))
                return static::{$intialiser->parent->getName()}();

            if($this->parent->hasDefaultValue()) 
                return $this->parent->getDefaultValue();

            $sqlType = $this->column->getType();

            if($sqlType->hasDefault()) {
                $phpType = $sqlType->getScalarType();

                if($this->parent->hasType()) {
                    $phpType = $this->parent->getType()->getName();

                    if(!\Cls::exists($phpType)) {
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
            //             ? \Cls::exists($type = ($this->parent->hasType() ? $this->parent->getType()->getName() : $this->column->getScalarType())) ? \Type::getByName()
            //             : false
            //     )
        }

        public function setSqlColumn(SqlColumn $column): void {
            $this->column = $column;
            
            $propertyAllowsNull = $this->parent->hasType() ? ($this->parent->getType()->allowsNull()) : true;
            $columnAllowsNull = $column->isIncremental() ?: $column->isNullable();

            // if($this->parent->getName() == "createdAt") {
            //     debug($this->parent->getName(), "<br>");
            //     debug($propertyAllowsNull, "<br>");
            //     debug($columnAllowsNull, "<br>");
            // }

            if($columnAllowsNull === false && $propertyAllowsNull)
                throw new \Error(\Str::format(
                    "Column property {}::\${} must not allow null to match the database.",
                    $this->parent->getDeclaringClass()->getName(),
                    $this->parent->getName()
                ));

            if($columnAllowsNull && $propertyAllowsNull === false)
                throw new \Error(\Str::format(
                    "Column property {}::\${} must allow null to match the database.",
                    $this->parent->getDeclaringClass()->getName(),
                    $this->parent->getName()
                ));
        }

        public function getForeignDesign(): EntityDesign|null {
            $declarer = $this->parent->getDeclaringClass();
            $schema = $declarer->getConstant("SCHEMA");
            $table  = $declarer->getConstant("TABLE");

            if($this->isForeignKey()) {
                $designs = EntityDesign::byReference($schema, $table);
                $count   = count($designs);

                if($count > 1) {
                    throw new \Error(\Str::format(
                        "The column {}::\${} foreign key resolves to multiple classes, explicit clarification is required.",
                        $this->parent->getDeclaringClass()->getName(),
                        $this->parent->getName()
                    ));
                }
                
                if($count === 1) {
                    return $designs[0];
                }
                else {
                    throw new \Error(\Str::format(
                        "The column {}::\${} foreign key resolves to no classes, explicit clarification is required.",
                        $this->parent->getDeclaringClass()->getName(),
                        $this->parent->getName()
                    ));
                }
            }
            else {
                throw new \Error(\Str::format(
                    "Column {}::\${}({}) isnt a foreign key.",
                    $declarer->getName(),
                    $this->parent->getName(),
                    $declarer->getMethod("ref")->invoke(null, $this->getColumnName())
                ));
            }
        }

        public function getForeignClass(): string|null {
            $declarer = $this->parent->getDeclaringClass();

            if(($foreignDesign = $this->getForeignDesign()) !== null) {
                return $foreignDesign->getName();
            }

            throw new \Error(\Str::format(
                "Column {}::\${}({}) isnt a foreign key.",
                $declarer->getName(),
                $this->parent->getName(),
                $declarer->invokeMethod("ref", $this->getColumnName())
            ));
        }

        public function getForeignPropertyAttribute(): static|null {
            if(($foreignColumn = $this->column->getForeignColumn()) !== null ? ($foreignDesign = $this->getForeignDesign()) : false) {
                return $foreignDesign->getAttrInstance(Column::class, $foreignColumn);
            }

            throw new \Error(\Str::format(
                "Unable to get foreign property attribute for {}::\${}.",
                $this->parent->getDeclaringClass()->getShortName(),
                $this->parent->getName()
            ));
        }

        public function getForeignProperty(): string|null {
            if(($foreignPropertyAttribute = $this->getForeignPropertyAttribute()) !== null) {
                return $foreignPropertyAttribute->parent->getName();
            }

            throw new \Error();
        }

        public function isForeignKey(): bool {
            return $this->column->isForeignKey() ?: false;
        }

        public function requiresValidation(): bool {
            return $this->validate ?: false;
        }

        public function isIncremental(): bool {
            return $this->column->isIncremental() ?: false;
        }

        public function isNullable(): bool {
            return $this->column->isNullable() ?: false;
        }

        public function isGenerated(): bool {
            return $this->column->isGenerated() ?: false;
        }

        public function isPrimaryKey(): bool {
            return $this->column->isPrimaryKey() ?: false;
        }
    }
}

?>