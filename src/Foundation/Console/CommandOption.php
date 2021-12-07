<?php

namespace Slate\Foundation\Console {

    use Attribute;

    #[Attribute(Attribute::TARGET_PROPERTY)]
    class CommandOption extends CommandExtra {
        public function isRequired(): bool {
            return (
                !$this->parent->hasDefaultValue()
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