<?php declare(strict_types = 1);

namespace Slate\Sql\Trait {
    use Slate\Facade\App;
    use Slate\Sql\SqlConnection;

    trait TSqlUsingConnection {
        protected string|SqlConnection|null $conn = null;

        public function using(string|SqlConnection $conn): static {
            if(is_string($conn))
                $conn = App::conn($conn);
    
            $this->conn = $conn;
    
            return $this;
        }

        public function conn(): SqlConnection {
            return $this->conn ?? App::conn();
        }
    }
}

?>