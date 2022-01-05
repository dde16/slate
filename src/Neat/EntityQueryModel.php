<?php

namespace Slate\Neat {

    use Slate\Structure\Struct;

    class EntityQueryModel extends Struct {
        public array $properties = [];
        public array $relationships = [];
        public EntityQueryVertex $vertex;
    }
    
}

?>