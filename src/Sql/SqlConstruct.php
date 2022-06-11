<?php declare(strict_types = 1);

namespace Slate\Sql {
    use Slate\Neat\Model;
    use Slate\Data\Contract\IStringForwardConvertable;
    use Slate\Data\TStringNativeForwardConvertable;
    use Slate\Sql\Contract\ISqlable;
    use Slate\Sql\Trait\TSqliser;

    abstract class SqlConstruct implements ISqlable { 
        use TSqliser;
    }
}

?>