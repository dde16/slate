<?php declare(strict_types = 1);

namespace Slate\Sql\Statement {
    use Slate\Facade\DB;
    use Slate\Facade\App;

trait TSqlSelectStatementChunk {
        public function chunk(int $size, int $from = 0) {
            $page = $from;

            $statement = clone $this;
            $statement = $this->conn->prepare(
                $statement->limit('?', '?')->toString(),
                [\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true]
            );

            while(true) {
                $statement->closeCursor();
                
                $statement->bindValue(1, ($size), \PDO::PARAM_INT);
                $statement->bindValue(2, ($size*($page)), \PDO::PARAM_INT);

                if($statement->execute()) {
                    $rowsSet = $statement->fetchAll(\PDO::FETCH_ASSOC);
                    $rowsCount = count($rowsSet);

                    if($rowsCount === 0)
                        return;

                    yield $rowsSet;

                    if($rowsCount < $size)
                        return;

                    // count($rows = iterator_to_array()) > 0
                }
                else {
                    break;
                }

                $page++;
            }
        }
    }
}

?>