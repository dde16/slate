<?php declare(strict_types = 1);

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

        public function anchor(): void {
            $this->anchors[++$this->anchor] = $this->key();
        }

        public function revert(): void {
            if($this->anchor < 0)
                throw new \Error("Trying to revert a non-existent anchor.");

            $this->seek($this->anchors[$this->anchor--]);
        }
    }
}

?>