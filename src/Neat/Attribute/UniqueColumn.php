<?php

namespace Slate\Neat\Attribute {
    use ReflectionProperty;
    use Slate\Metalang\MetalangDesign;
    use Slate\Neat\Attribute\Column;

    class UniqueColumn extends Column {
        public function __construct(
            string $name = null,
            string $type = null,
            bool $incremental = null
        ) {
            parent::__construct($name, $type, $incremental, null);

            $this->columnUnique = true;
        }
    }
}

?>