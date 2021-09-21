<?php

namespace Slate\Neat {

    use Slate\Facade\DB;
    use Slate\Neat\Entity;
    use Slate\Sql\Statement\SqlSelectStatement;

    class EntityQueryRootVertex extends EntityQueryVertex {
        public function modifyQuery(SqlSelectStatement $query): void {
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

                        $query->where($rowVariable, "BETWEEN", DB::raw(($this->offset ?: 0) . " AND " . $this->limit));
                    }
                    else if($this->offset !== null) {

                        $query->where($rowVariable, ">=", $this->offset);
                    }
                }
                else if(\Arr::isEmpty($this->orderBy)) {
                    if($this->limit !== null) {
                        $query->limit = $this->limit;
                    }
                    
                    if($this->offset !== null) {
                        $query->offset = $this->offset;
                    }
                }
            }

            foreach($this->scopes as list($scope, $arguments)) {
                $scope->parent->invokeArgs(null, [$query, ...$arguments]);
            }
    
            // return $query;
        }
    }
}

?>