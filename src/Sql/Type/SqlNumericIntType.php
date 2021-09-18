<?php

namespace Slate\Sql\Type {

    use DateTime;

    class SqlNumericIntType extends SqlNumericType {

        public function getScalarType(): string {
            return \Integer::class;
        }
    }
}

?>