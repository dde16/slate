<?php

namespace Slate\Neat {

    use Slate\Neat\Attribute\OneToAny;
    use Slate\Neat\Attribute\OneToOne;

    class EntityQuerySubVertex extends EntityQueryVertex {
        /**
         * The attribute to which this relationship will 'run along'.
         *
         * @var OneToAny
         */
        public OneToAny $along;
    
        /**
         * Details the type of join it is.
         *
         * @var boolean
         */
        public bool $optional = false;
    
        public function along(OneToAny $along) {
            $this->along = $along;
    
            if($along instanceof OneToOne)
                $this->limit(1);
        }
    }
}

?>