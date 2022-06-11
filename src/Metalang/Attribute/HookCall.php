<?php declare(strict_types = 1);

namespace Slate\Metalang\Attribute {
    use Attribute;
    use Slate\Metalang\Hook;

    #[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
    class HookCall extends Hook {
        
    }
}

?>