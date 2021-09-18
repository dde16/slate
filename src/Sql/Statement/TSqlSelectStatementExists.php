<?php

namespace Slate\Sql\Statement {
    use Slate\Facade\DB;
    use Slate\Facade\Sql;
    use Slate\Mvc\App;
    use Slate\Sql\SqlReference;

    trait TSqlSelectStatementExists {
        
        public function exists(): bool {
            $columns = $this->columns;
            $this->columns = [new SqlReference("1")];

            $query = clone $this;
            
            $this->columns = $columns;

            $query->limit(1);
            $query = DB::select()->column(Sql::exists($query), as: "`EXISTS`")->toString();
            
            $conn = App::conn($this->conn);

            return @$conn->soloquery($query)->current()["EXISTS"] ?: false;
        }

    }
}

?>