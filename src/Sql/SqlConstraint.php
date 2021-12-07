<?php

namespace Slate\Sql {


    abstract class SqlConstraint extends SqlConstruct {
        use TSqlUsingConnection;

        use TSqlIndex {
            TSqlIndex::build as buildIndex;
        }

        public const MODIFIERS = SqlModifier::VISIBILITY;

        protected string $synonym = "KEY";

        protected ?string $symbol = null;

        public function __construct(string $symbol = null) {
            $this->symbol = $symbol;
        }

        public function fromArray(array $array): void {
            $this->symbol = $array["name"];
        }
    }
}

?>
