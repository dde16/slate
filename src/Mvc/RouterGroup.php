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
    
        protected ?Closure $callback = null;
    
        public function __construct(array $options, ?Closure $callback = null) {
            parent::__construct($options);
            $this->callback = $callback;
        }

        public function group(Closure $closure): static {
            $this->callback = $closure;

            return $this;
        }
    
        public function __invoke(): void {
            ($this->callback)();
        }
    }
}

?>