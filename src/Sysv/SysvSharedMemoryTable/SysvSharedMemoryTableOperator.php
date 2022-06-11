<?php declare(strict_types = 1);

namespace Slate\Sysv\SysvSharedMemoryTable {

    use Slate\Structure\Enum;

    class SysvSharedMemoryTableOperator extends Enum {
        public const EQUAL                 = (1<<0);
        public const NOT_EQUAL             = (1<<1);
        public const MATCHES               = (1<<2);
        public const LIKE                  = (1<<3);
        public const LESS_THAN             = (1<<4);
        public const LESS_THAN_OR_EQUAL    = (1<<5);
        public const GREATER_THAN          = (1<<6);
        public const GREATER_THAN_OR_EQUAL = (1<<7);
        public const IN                    = (1<<8);
    }
}

?>