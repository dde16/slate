<?php declare(strict_types = 1);

namespace Slate\Sql\Clause {
    use Slate\Facade\Sql;

    trait TSqlOnDuplicateKeyUpdateClause {
        protected ?string $conflictMode = null;
        protected array  $conflictStore = [];

        public function conflictMirror(): static {
            $this->conflictMode = "mirror";

            return $this;
        }

        public function conflictUpdate(array $set): static {
            $this->conflictMode = "update";
            $this->conflictStore = $set;

            return $this;
        }

        public function conflictIgnore(): static {
            $this->conflictMode = "ignore";
            
            return $this;
        }
        
        public function  buildOnDuplicateKeyUpdateClause() {
            if($this->conflictMode === null) {
                return;
            }

            if($this->conflictMode === "mirror" && \Arr::isEmpty($this->columns)) {
                return;
            }

            switch($this->conflictMode) {
                case "mirror":
                    $updates = \Arr::mapAssoc(
                        $this->columns,
                        function($index, $key) { 
                            return ["`$key`", "VALUES(`$key`)"];
                        }
                    );
                    break;
                case "ignore":
                    $updates = \Arr::mapAssoc(
                        $this->columns,
                        function($index, $key) { 
                            return ["`$key`", "`$key`"];
                        }
                    );
                    break;
                case "update":
                    $updates = \Arr::map(
                        $this->conflictStore,
                        function($value) {
                            return Sql::sqlify($value);
                        }
                    );

                    break;
            }

            return "ON DUPLICATE KEY UPDATE " . \Arr::join(
                    \Arr::map(
                        \Arr::entries(
                            $updates
                        , generator: false),
                        function($entry) {
                            return \Arr::join($entry, "=");
                        }
                    ),
                    ", "
                );
        }
    }
}

?>