<?php declare(strict_types = 1);

namespace Slate\Sql {

    use Slate\Sql\Contract\ISqlable;
    use Slate\Sql\Trait\TSqliser;

    abstract class SqlClause implements ISqlable {
        use TSqliser;
    }
}

?>