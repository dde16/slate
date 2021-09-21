<?php

namespace Slate\Utility {
    trait TImitate {
        use TSingleton;

        /**
         * A method to create an instance for this class.
         * 
         * @return object
         */
        protected abstract static function createInstance(): object;
    }
}

?>