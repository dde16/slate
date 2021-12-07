<?php

namespace Slate\Neat\Attribute {

    use Attribute;
    use ReflectionProperty;
    use Slate\Metalang\MetalangDesign;

#[Attribute(Attribute::TARGET_PROPERTY)]
    class PrimaryColumn extends Column {
        public function __construct(
            string $name = null,
            string $type = null,
            bool $incremental = null
        ) {
            parent::__construct($name, $type, $incremental, null);

            $this->columnPrimary = true;
        }
    }
}

?>