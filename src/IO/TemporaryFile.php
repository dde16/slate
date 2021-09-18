<?php

namespace Slate\IO {
    class TemporaryFile extends File {
        public function close(): bool {
            $stat = parent::close();

            // flock($this->resource, LOCK_UN);
            
            return $stat && unlink($this->path);
        }
    }
}

?>