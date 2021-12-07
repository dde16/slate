<?php

namespace Slate\Foundation\Console {

    use Attribute;

    #[Attribute(Attribute::TARGET_PARAMETER)]
    class CommandArgument extends CommandExtra {
            
        public function isRequired(): bool {
            return (
                !$this->parent->isOptional()
                    ?
                    ($this->parent->hasType()
                        ? !$this->parent->getType()->allowsNull()
                        : false)
                    : false
            );
        }
    }

}

?>