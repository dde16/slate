<?php

namespace Slate\Sql {

    use Slate\Data\IStringForwardConvertable;
    use Slate\Data\TStringNativeForwardConvertable;

    abstract class SqlClause implements IStringForwardConvertable {
        use TStringNativeForwardConvertable;
    }
}

?>