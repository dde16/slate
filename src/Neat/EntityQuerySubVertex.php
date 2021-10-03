<?php

namespace Slate\Neat {

    use Slate\Facade\Sql;
    use Slate\Neat\Attribute\Column;
    use Slate\Neat\Attribute\Scope;
    use Slate\Neat\Entity;
    use Slate\Sql\SqlReference;
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
        
        public function modifyQuery(SqlSelectStatement $query, EntityQueryVertex $foreignVertex = null): void {
            if($this->limit !== null || $this->offset !== null) {
                $rowNumber =
                    Sql::winfn("ROW_NUMBER")
                        ->partitionBy(
                            $this->entity::ref($this->column->getColumnName())->toString()
                        )

                        ->{"orderBy" . ucfirst(\Str::lower($this->orderDirection ?: "ASC"))}(
                            ...(!\Arr::isEmpty($this->orderBy ?: [])
                                ? \Arr::map(
                                    $this->orderBy,
                                    function($orderBy) {
                                        return ($orderBy instanceof EntityReference) ? $orderBy->toString(Entity::REF_SQL) : $orderBy;
                                    }
                                )
                                : [
                                    $this->entity::ref($this->column->getColumnName())->toString()
                                ]
                            )
                        );

                $this->columns = [
                    (new SqlReference("*")), 
                    (new SqlReference($rowNumber))->as("`RowNumber`" )
                ];
                $this->orderBy = [];

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


            parent::modifyQuery($query, $foreignVertex);
        }
    }
}

?>