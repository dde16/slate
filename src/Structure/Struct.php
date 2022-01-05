<?php

namespace Slate\Structure {

    use Slate\Utility\TObjectHelpers;
    use Slate\Utility\TUninstantiable;

    class Struct {
        use TObjectHelpers;

        public function __construct(array $properties) {
            foreach($properties as $property => $value)
                if($this->hasProperty($property))
                    $this->{$property} = $value;
        }
    }
}

?>