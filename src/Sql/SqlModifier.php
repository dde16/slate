<?php declare(strict_types = 1);

namespace Slate\Sql {

    use Slate\Structure\Enum;

    final class SqlModifier extends Enum {
        public const ALL             = (1<<0);
        public const BIG_RESULT      = (1<<1);
        public const BUFFER_RESULT   = (1<<2);
        public const CALC_FOUND_ROWS = (1<<3);
        public const CASCADE         = (1<<4);
        public const CHECKSUM        = (1<<5);
        public const DELAYED         = (1<<6);
        public const DELAY_KEY_WRITE = (1<<7);
        public const DISTINCT        = (1<<8);
        public const DISTINCT_ROW    = (1<<9);
        public const ENCRYPTION      = (1<<10);
        public const FORCE           = (1<<11);
        public const HIGH_PRIORITY   = (1<<12);
        public const IF_EXISTS       = (1<<13);
        public const IF_NOT_EXISTS   = (1<<14);
        public const IGNORE          = (1<<15);
        public const LOW_PRIORITY    = (1<<16);
        public const NO_ACTION       = (1<<17);
        public const NO_CACHE        = (1<<18);
        public const QUICK           = (1<<19);
        public const REPLACE         = (1<<20);
        public const RESTRICT        = (1<<21);
        public const SET_DEFAULT     = (1<<22);
        public const SET_NULL        = (1<<23);
        public const SMALL_RESULT    = (1<<24);
        public const STRAIGHT_JOIN   = (1<<25);
        public const TEMPORARY       = (1<<26);
        public const VISIBILITY      = (1<<27);
    
        public const DEFINITIONS = [
            SqlModifier::ALL             => "ALL",
            SqlModifier::BIG_RESULT      => "SQL_BIG_RESULT",
            SqlModifier::BUFFER_RESULT   => "SQL_BUFFER_RESULT",
            SqlModifier::CALC_FOUND_ROWS => "SQL_CALC_FOUND_ROWS",
            SqlModifier::CASCADE         => "CASCADE",
            SqlModifier::CHECKSUM        => ["CHECKSUM", ["0", "1"]],
            SqlModifier::DELAYED         => "DELAYED",
            SqlModifier::DELAY_KEY_WRITE => ["DELAY_KEY_WRITE", ["0", "1"]],
            SqlModifier::DISTINCT        => "DISTINCT",
            SqlModifier::DISTINCT_ROW    => "DISTINCT ROW",
            SqlModifier::ENCRYPTION      => ["ENCRYPTION", ["N", "Y"]],
            SqlModifier::FORCE           => "FORCE",
            SqlModifier::HIGH_PRIORITY   => "HIGH PRIORITY",
            SqlModifier::IF_EXISTS       => "IF EXISTS",
            SqlModifier::IF_NOT_EXISTS   => "IF NOT EXISTS",
            SqlModifier::IGNORE          => "IGNORE",
            SqlModifier::LOW_PRIORITY    => "LOW PRIORITY",
            SqlModifier::NO_ACTION       => "NO ACTION",
            SqlModifier::NO_CACHE        => "SQL_NO_CACHE",
            SqlModifier::QUICK           => "QUICK",
            SqlModifier::REPLACE         => "REPLACE",
            SqlModifier::RESTRICT        => "RESTRICT",
            SqlModifier::SET_DEFAULT     => "SET DEFAULT",
            SqlModifier::SET_NULL        => "SET NULL",
            SqlModifier::SMALL_RESULT    => "SQL_SMALL_RESULT",
            SqlModifier::STRAIGHT_JOIN   => "STRAIGHT JOIN",
            SqlModifier::TEMPORARY       => "TEMPORARY",
            SqlModifier::VISIBILITY      => [null, ["INVISIBLE", "VISIBLE"]]
        ];
    
        public const TOGGLERS = [
            "all"           => SqlModifier::ALL,
            "bigResult"     => SqlModifier::BIG_RESULT,
            "bufferResult"  => SqlModifier::BUFFER_RESULT,
            "calcFoundRows" => SqlModifier::CALC_FOUND_ROWS,
            "cascade"       => SqlModifier::CASCADE,
            "checksum"      => SqlModifier::CHECKSUM,
            "delayed"       => SqlModifier::DELAYED,
            "delayKeyWrite" => SqlModifier::DELAY_KEY_WRITE,
            "distinct"      => SqlModifier::DISTINCT,
            "distinctRow"   => SqlModifier::DISTINCT_ROW,
            "encryption"    => SqlModifier::ENCRYPTION,
            "force"         => SqlModifier::FORCE,
            "highPriority"  => SqlModifier::HIGH_PRIORITY,
            "ifExists"      => SqlModifier::IF_EXISTS,
            "ifNotExists"   => SqlModifier::IF_NOT_EXISTS,
            "ignore"        => SqlModifier::IGNORE,
            "lowPriority"   => SqlModifier::LOW_PRIORITY,
            "noAction"      => SqlModifier::NO_ACTION,
            "noCache"       => SqlModifier::NO_CACHE,
            "quick"         => SqlModifier::QUICK,
            "replace"       => SqlModifier::REPLACE,
            "restrict"      => SqlModifier::RESTRICT,
            "setDefault"    => SqlModifier::SET_DEFAULT,
            "setNull"       => SqlModifier::SET_NULL,
            "smallResult"   => SqlModifier::SMALL_RESULT,
            "straightJoin"  => SqlModifier::STRAIGHT_JOIN,
            "temporary"     => SqlModifier::TEMPORARY,
            "invisible"     => [SqlModifier::VISIBILITY, false],
            "visible"       => [SqlModifier::VISIBILITY, true]
        ];
    }
}

?>