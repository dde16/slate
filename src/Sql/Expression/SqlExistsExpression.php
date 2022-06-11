<?php declare(strict_types = 1);

namespace Slate\Sql\Expression {

    use Slate\Data\Contract\IStringForwardConvertable;
    use Slate\Data\TStringNativeForwardConvertable;
    use Slate\Sql\Contract\ISqlable;
    use Slate\Sql\SqlConstruct;

    class SqlExistsExpression implements IStringForwardConvertable { 
        use TStringNativeForwardConvertable;

        protected IStringForwardConvertable|ISqlable $source;

        public function __construct(IStringForwardConvertable|ISqlable $source) {
            $this->source = $source;
        }

        public function toString(): string {
            $build = [
                "EXISTS"
            ];

            if($this->source instanceof IStringForwardConvertable)
                $build[] = \Str::wrapc($this->source->__toString(), "()");
            else if($this->source instanceof ISqlable)
                $build[] = \Str::wrapc($this->source->toSql(), "()");

            return \Arr::join($build, " ");
        }
    }
}

?>