<?php declare(strict_types = 1);

namespace Slate\Sql\Clause {
    trait TSqlEngineAttributeClause {
        protected ?string $primaryEngineAttribute   = null;
        protected ?string $secondaryEngineAttribute = null;

        public function engineAttr(string $value, int $index = 0): static {
            $this->{($index === 0 ? "primary" : "secondary")."EngineAttribute"}= $value;

            return $this;
        }

        public function buildEngineAttributeClause(): ?string {
            $attrs = [];

            if($this->primaryEngineAttribute !== null) {
                $attrs[] = "ENGINE_ATTRIBUTE=" . $this->primaryEngineAttribute;
            }

            if($this->secondaryEngineAttribute !== null) {
                $attrs[] = "SECONDARY_ENGINE_ATTRIBUTE=" . $this->secondaryEngineAttribute;
            }

            return !\Arr::isEmpty($attrs) ? \Arr::join($attrs, " ") : null;
        }
    }
}

?>