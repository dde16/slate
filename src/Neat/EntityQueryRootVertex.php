<?php

namespace Slate\Neat {

    use Slate\Facade\DB;
    use Slate\Facade\Sql;
    use Slate\Neat\Entity;
    use Slate\Sql\SqlReference;
    use Slate\Sql\Statement\SqlSelectStatement;

    class EntityQueryRootVertex extends EntityQueryVertex {
        public function modifyQuery(SqlSelectStatement $query, EntityQueryVertex $foreignVertex = null): void {
            parent::modifyQuery($query, $foreignVertex);

            if($this->limit !== null || $this->offset !== null) {
                if($this->hasEdges()) {
                    $query->var("rowCache", "NULL");
                    $query->var("rowNumber", "0");
        
                    $rowVariable =
                        \Str::format(
                            "IF(@rowCache != {} OR ISNULL(@rowCache), IF(NOT(ISNULL(@rowCache := {})), @rowNumber := @rowNumber + 1, @rowNumber := @rowNumber + 1), @rowNumber)",
                            // "IF(
                            //     @rowCache != {} OR ISNULL(@rowCache),
                            //     IF(
                            //         NOT(ISNULL(@rowCache := {})),
                            //         @rowNumber := @rowNumber + 1,
                            //         @rowNumber := @rowNumber + 1
                            //     ),
                            //     @rowNumber
                            // )",
                            \Arr::repeat($this->entity::ref($this->entity::design()->getPrimaryKey()->getColumnName(), Entity::REF_RESOLVED | Entity::REF_ITEM_WRAP), 2)
                        );

                    if($this->limit !== null) {
                        $query->trailingWheres[] = ["and", [$rowVariable, "BETWEEN", DB::raw(($this->offset ?: 0) . " AND " . $this->limit)]];
                    }
                    else if($this->offset !== null) {
                        $query->trailingWheres[] = ["and", [$rowVariable, ">=", $this->offset]];
                    }
                }
                else {
                    $query->orderBy        = $this->orderBy;
                    $query->orderDirection = $this->orderDirection;

                    if($this->limit !== null)
                        $query->limit = $this->limit;
                    
                    if($this->offset !== null)
                        $query->offset = $this->offset;

                    $this->orderBy = [];
                }
            }
            else if(!\Arr::isEmpty($this->orderBy)) {
                $query->orderBy        = $this->orderBy;
                $query->orderDirection = $this->orderDirection;

                $this->orderBy = [];
            }
        }
    }
}

?>