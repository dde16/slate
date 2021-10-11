<?php

namespace Slate\Data {

use ArrayAccess;

class FieldPrepped extends Field {
        protected array $sources = [];
    
        public function from(ArrayAccess|array ...$sources): static {
            foreach($sources as $source)
                $this->sources[] = $source;
    
            return $this;
        }
    
        public function get(bool|string $assert = true): mixed {
            foreach(\Arr::describe($this->sources) as list($position, $source)) {
                list($sourced, $value) = $this->getFrom($source, boolval($position & \Arr::POS_END) ? $assert : false);
    
                if($sourced)
                    return $value;
            }
    
            return null;
        }
    }
}

?>