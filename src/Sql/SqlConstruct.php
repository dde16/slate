<?php

namespace Slate\Sql {
    use Slate\Neat\Model;
    use Slate\Data\IStringForwardConvertable;
    use Slate\Data\TStringNativeForwardConvertable;

    abstract class SqlConstruct extends Model implements IStringForwardConvertable { 
        use TStringNativeForwardConvertable;
        
        public function __invoke() {
            return $this;
        }

        public abstract function build(): array;
    
        public function toString(): string {
            return \Arr::join(\Arr::filter($this->build()), " ");
        }
    }
}

?>