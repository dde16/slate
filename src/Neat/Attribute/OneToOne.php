<?php

namespace Slate\Neat\Attribute {
    use Attribute;
    use ReflectionProperty;
    use ReflectionUnionType;
    use Slate\Metalang\MetalangDesign;
    use Slate\Neat\EntityDesign;
    use Slate\Sql\Constraint\SqlForeignKeyConstraint;
    use Slate\Sql\SqlTable;

#[Attribute(Attribute::TARGET_PROPERTY)]
    class OneToOne extends OneToAny {
        public array|string|null $foreignRelationship = null;

        protected ?string $symbol;
        protected ?string $onUpdate;
        protected ?string $onDelete;

        public function __construct(
            string $localProperty,
            array $foreignRelationship = null,
            string $symbol = null,
            string $onUpdate = null,
            string $onDelete = null
        ) {
            parent::__construct($localProperty, $foreignRelationship);

            $this->symbol = $symbol;
            $this->onUpdate = $onUpdate;
            $this->onDelete = $onDelete;
        }

        public function getConstraint(string $entity): void {
            $design = $entity::design();

            if(($localColumnAttribute = $design->getAttrInstance(Column::class, $this->localProperty)) !== null) {
                $localColumn = $localColumnAttribute->getColumn($entity);

                $foreignClass = $this->foreignImmediateClass;
                $foreignDesign = $foreignClass::design();
                $foreignColumn = $foreignDesign->getAttrInstance(Column::class, $this->foreignImmediateProperty);

                if(!$localColumn->foreignKeyConstraint) {
                    $localColumn->foreignKeyConstraint = new SqlForeignKeyConstraint($localColumn, $this->symbol);

                    if($this->onUpdate)
                        $localColumn->foreignKeyConstraint->onUpdate($this->onUpdate);

                    if($this->onDelete)
                        $localColumn->foreignKeyConstraint->onDelete($this->onDelete);

                    $localColumn->foreignKeyConstraint->references(
                        $foreignClass::SCHEMA,
                        $foreignClass::TABLE,
                        $foreignColumn->getColumnName()
                    );
                }
            }
            else {
                throw new \Error("Unknown ");
            }
        }
    }
}

?>