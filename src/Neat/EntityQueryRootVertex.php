<?php

namespace Slate\Neat {

    use Generator;
    use Iterator;

class EntityQueryRootVertex extends EntityQueryVertex {
        /**
         * The left hand model key if it applies.
         *
         * @var object
         */
        public string|int|null $model = null;
    
        public function children(Iterator $parentModels = null): Generator {
            if($parentModels === null) {
                $query = $this->query();
                $parentModels = $query->get();
            }
    
            return parent::children($parentModels);
        }
    
    }
    
}
    

?>