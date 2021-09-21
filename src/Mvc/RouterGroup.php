<?php

namespace Slate\Mvc {

    use Closure;
    use Slate\Data\IJitStructureGroup;
    use Slate\Neat\Attribute\Fillable;
    use Slate\Neat\Model;

    class RouterGroup extends Model implements IJitStructureGroup {
        #[Fillable]
        protected ?string $domain = null;
    
        #[Fillable]
        protected ?string $prefix = null;
    
        #[Fillable]
        protected ?string $name   = null;
    
        protected Closure $callback;
    
        public function __construct(array $options, Closure $callback) {
            parent::__construct($options);
            $this->callback = $callback;
        }
    
        public function __invoke(): void {
            ($this->callback)();
        }
    }
}

?>