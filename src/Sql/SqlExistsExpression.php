<?php

namespace Slate\Sql {

    use Slate\Data\IStringForwardConvertable;
    use Slate\Data\TStringNativeForwardConvertable;

    class SqlExistsExpression implements IStringForwardConvertable { 
        use TStringNativeForwardConvertable;

        protected IStringForwardConvertable $source;

        public function __construct(IStringForwardConvertable $source) {
            $this->source = $source;
        }

        public function toString(): string {
            return "EXISTS " . \Str::wrapc($this->source ? $this->source->toString() : "SELECT 1", "()");
        }
    }
}

?>