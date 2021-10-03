<?php

namespace Slate\Sql\Clause {
    trait TSqlWithParserClause {
        protected ?string $parser = null;

        public function withParser(string $name): static {
            $this->parser = $name;

            return $this;
        }

        public function buildWithParserClause(): ?string {
            return $this->parser ? "WITH PARSER {$this->parser}" : null;
        }
    }
}

?>