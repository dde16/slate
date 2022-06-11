<?php declare(strict_types = 1);

namespace Slate\Sql {
    use Slate\Data\Contract\IStringForwardConvertable;
    use Slate\Data\TStringNativeForwardConvertable;

    class SqlRaw implements IStringForwardConvertable {
        use TStringNativeForwardConvertable;

        protected string $content;

        public function __construct(string $content) {
            $this->content = $content;
        }

        public function toString(): string {
            return $this->content;
        }
    }
}

?>