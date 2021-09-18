<?php

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

        
        public function  buildOnDuplicateKeyUpdateClause() {
            return $this->conflictMode !== null && !\Arr::isEmpty($this->columns)
                ? "ON DUPLICATE KEY UPDATE " . \Arr::join(
                    \Arr::map(
                        \Arr::entries(
                            $this->conflictMode == "mirror"
                            ? \Arr::mapAssoc(
                                $this->columns,
                                function($index, $key) { 
                                    return ["`$key`", "VALUES(`$key`)"];
                                }
                            )
                            : \Arr::map(
                                $this->conflictStore,
                                function($value) {
                                    return Sql::sqlify($value);
                                }
                            )
                        , generator: false),
                        function($entry) {
                            return \Arr::join($entry, "=");
                        }
                    ),
                    ", "
                )
                : null;
        }
    }
}

?>