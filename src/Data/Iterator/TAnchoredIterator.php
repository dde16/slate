<?php

namespace Slate\Data\Iterator {
    trait TAnchoredIterator {
        protected array $anchors = [];
        protected int   $anchor  = -1;

        public function getAnchors(): array {
            return $this->anchors;
        }

        public function getAnchor(): int {
            return $this->anchor;
        }

        public function deanchor(): void {
            $this->anchor--;
        }
    }
}

?>