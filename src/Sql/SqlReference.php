<?php

namespace Slate\Sql {

    use Slate\Data\IAnyForwardConvertable;
    use Slate\Data\IStringForwardConvertable;
    use Slate\Data\TStringNativeForwardConvertable;

class SqlReference implements IStringForwardConvertable {
        use TStringNativeForwardConvertable;

        public mixed $reference = null;
        public ?string $as        = null;

        public function __construct(string|IStringForwardConvertable $reference) {
            $this->reference = $reference;
        }

        public function as(string $as): static {
            $this->as = $as;

            return $this;
        }

        public function toString(): string {
            $ref = $this->reference;

            if(is_object($ref)) {
                if(\Cls::implements($ref, IAnyForwardConvertable::class )){
                    $ref = $ref->toAny();
                }

                if(is_object($ref)) {
                    if(\Cls::isSubclassInstanceOf($ref, SqlConstruct::class)) {
                        $ref = \Str::wrapc($ref->toString(), "()");
                    }
                    else {
                        $ref = $ref->toString();
                    }
                }
                
            }

            return (
                $ref
            ). ($this->as !== null ? " AS " . $this->as : "");
        }
    }
}

?>