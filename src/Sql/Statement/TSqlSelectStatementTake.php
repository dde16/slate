<?php declare(strict_types = 1);

namespace Slate\Sql\Statement {
    trait TSqlSelectStatementTake {
        public function take(int $amount, string $conn = null) {
            $this->limit($amount);

            return $this->get($conn);
        }
    }
}

?>