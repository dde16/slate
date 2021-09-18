<?php

namespace Slate\Neat {
    use Slate\Neat\Attribute\Column;
    use Slate\Neat\Attribute\Scope;
    use Slate\Neat\Entity;
    use Slate\Sql\Statement\SqlSelectStatement;

    class EntityQuerySubVertex extends EntityQueryVertex {
        public string  $flag;
        public Column  $column;
    
        public function __construct(string $entity, string $flag, Column $column, ?Scope $scope) {
            parent::__construct($entity);
    
            $this->column  = $column;

            if($scope !== null)
                $this->scopes[] = [$scope, []];

            $this->flag  = $flag;
    
            if(!\Arr::contains(["!", "?"], $flag))
                throw new \Error("Invalid flag '$flag'.");
        }
        
        public function modifyQuery(SqlSelectStatement $query): void {
            if($this->limit !== null || $this->offset !== null) {
                if($this->offset !== null) {
                    $query->where(
                        $this->entity::ref("RowNumber", Entity::REF_RESOLVED | Entity::REF_ITEM_WRAP),
                        ">=", 
                        $this->offset
                    );
                }
    
                if($this->limit !== null) {
                    $query->where(
                        $this->entity::ref("RowNumber", Entity::REF_RESOLVED | Entity::REF_ITEM_WRAP),
                        "<=", 
                        $this->limit
                    );
                }
            }

            foreach($this->scopes as list($scope, $arguments)) {
                $scope->parent->invokeArgs(null, [$query, ...$arguments]);
            }
        }
    }
}

?>