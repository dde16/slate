<?php

namespace Slate\Sql {

    use Slate\Data\IStringForwardConvertable;
    use Slate\Data\TStringNativeForwardConvertable;
    use Slate\Sql\Clause\SqlOrderByClause;
    use Slate\Sql\Clause\SqlPartitionByClause;
    use Slate\Sql\Clause\TSqlOrderByClause;
    use Slate\Sql\Clause\TSqlPartitionByClause;

    class SqlWindowFunction implements IStringForwardConvertable {
        use TStringNativeForwardConvertable ;

        use TSqlOrderByClause {
            TSqlOrderByClause::buildOrderByClause as protected _buildOrderByClause;
        }

        use TSqlPartitionByClause {
            TSqlPartitionByClause::buildPartitionByClause as protected _buildPartitionByClause;
        }

        protected string                $name;

        public function __construct(string $name) {
            $this->name = $name;
        }

        public function toString(): string {
            return \Arr::join([
                $this->name."()",
                "OVER",
                \Str::wrapc(
                    \Arr::join(
                        \Arr::filter([
                            $this->_buildPartitionByClause(),
                            $this->_buildOrderByClause()
                        ]),
                        " "
                    ),
                    "()"
                )
            ], " ");
        }

    }
}

?>