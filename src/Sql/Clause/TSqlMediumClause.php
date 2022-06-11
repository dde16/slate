<?php declare(strict_types = 1);

namespace Slate\Sql\Clause {
    trait TSqlMediumClause {
        protected string $type;
        protected string $medium;

        public function table(string $name): static {
            $this->medium = $name;
            $this->type = "TABLE";

            return $this;
        }

        public function schema(string $name): static {
            $this->medium = $name;
            $this->type = "SCHEMA";

            return $this;
        }
    }
}

?>