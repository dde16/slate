<?php

namespace Slate\Neat\Attribute {
    use Attribute;

    #[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
    class Property extends Accessor { }
}

?>