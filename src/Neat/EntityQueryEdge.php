<?php

namespace Slate\Neat {
    use Slate\Neat\Attribute\OneToAny;

    class EntityQueryEdge {
        public string|int $id;
        public OneToAny     $along;
    
        public function __construct(string|int $id, OneToAny $along) {
            $this->id = $id;
            $this->along = $along;
        }
    
        public static function fromArray(array $array): static {
            return(new static($array["id"], $array["along"]));
        } 
    }
}

?>