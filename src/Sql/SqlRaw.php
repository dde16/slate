<?php

namespace Slate\Sql {
    use Slate\Data\IStringForwardConvertable;
    use Slate\Data\TStringNativeForwardConvertable;

    class SqlRaw implements IStringForwardConvertable {
        use TStringNativeForwardConvertable;

        public function __construct(
            protected string $content
        ) {}

        public function toString(): string {
            return $this->content;
        }
    }
}

?>