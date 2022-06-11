<?php declare(strict_types = 1);

namespace Slate\Neat\Attribute {
    use Attribute;
    
    #[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
    class Getter extends Accessor { }
}

?>