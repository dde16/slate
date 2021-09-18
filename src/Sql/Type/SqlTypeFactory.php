<?php

namespace Slate\Sql\Type {
    use Slate\Utility\Factory;

    use Slate\Sql\Type\MySql\MySqlDateType;
    use Slate\Sql\Type\MySql\MySqlTimeType;
    use Slate\Sql\Type\MySql\MySqlDateTimeType;
    use Slate\Sql\Type\MySql\MySqlTimestampType;
    use Slate\Sql\Type\MySql\MySqlYearType;

    use Slate\Sql\Type\PgSql\PgSqlTimeType;
    use Slate\Sql\Type\PgSql\PgSqlTimestampType;
    use Slate\Sql\Type\PgSql\PgSqlDateType;

    use Slate\Sql\Type\MsSql\MsSqlDateType;
    use Slate\Sql\Type\MsSql\MsSqlDateTimeoffsetType;
    use Slate\Sql\Type\MsSql\MsSqlDateTime2Type;
    use Slate\Sql\Type\MsSql\MsSqlSmalldatetimeType;
    use Slate\Sql\Type\MsSql\MsSqlDateTimeType;
    use Slate\Sql\Type\MsSql\MsSqlTimeType;

    class SqlTypeFactory extends Factory {
        public const MAP = [
            /** Integers */
            "mysql.tinyint"          => SqlNumericIntType::class,
            "mysql.smallint"         => SqlNumericIntType::class,
            "mysql.mediumint"        => SqlNumericIntType::class,
            "mysql.int"              => SqlNumericIntType::class,
            "mysql.bigint"           => SqlNumericIntType::class,
            "mysql.bit"              => SqlNumericIntType::class,

            "mssql.bigint"           => SqlNumericIntType::class,
            "mssql.bit"              => SqlNumericIntType::class,
            "mssql.smallint"         => SqlNumericIntType::class,
            "mssql.int"              => SqlNumericIntType::class,
            "mssql.tinyint"          => SqlNumericIntType::class,

            "pgsql.smallint"         => SqlNumericIntType::class,
            "pgsql.integer"          => SqlNumericIntType::class,
            "pgsql.bigint"           => SqlNumericIntType::class,
            "pgsql.smallserial"      => SqlNumericIntType::class,
            "pgsql.serial"           => SqlNumericIntType::class,
            "pgsql.bigserial"        => SqlNumericIntType::class,

            /** Real/Floating Point/Decimal Numbers */
            "mysql.decimal"          => SqlNumericRealType::class,
            "mysql.float"            => SqlNumericRealType::class,
            "mysql.double"           => SqlNumericRealType::class,

            "mssql.float"            => SqlNumericRealType::class,
            "mssql.real"             => SqlNumericRealType::class,
            "mssql.numeric"          => SqlNumericRealType::class,
            "mssql.decimal"          => SqlNumericRealType::class,
            "mssql.smallmoney"       => SqlNumericRealType::class,
            "mssql.money"            => SqlNumericRealType::class,

            "pgsql.decimal"          => SqlNumericRealType::class,
            "pgsql.numeric"          => SqlNumericRealType::class,
            "pgsql.real"             => SqlNumericRealType::class,
            "pgsql.double precision" => SqlNumericRealType::class,


            /** Date and Time */
            "mysql.date"             => MySqlDateType::class,
            "mysql.time"             => MySqlTimeType::class,
            "mysql.datetime"         => MySqlDateTimeType::class,
            "mysql.timestamp"        => MySqlTimestampType::class,
            "mysql.year"             => MySqlYearType::class,

            "pgsql.time"             => PgSqlTimeType::class,
            "pgsql.timestamp"        => PgSqlTimestampType::class,
            "pgsql.date"             => PgSqlDateType::class,

            "mssql.date"             => MsSqlDateType::class,
            "mssql.datetimeoffset"   => MsSqlDateTimeoffsetType::class,
            "mssql.datetime2"        => MsSqlDateTime2Type::class,
            "mssql.smalldatetime"    => MsSqlSmalldatetimeType::class,
            "mssql.datetime"         => MsSqlDateTimeType::class,
            "mssql.time"             => MsSqlTimeType::class,

            /** String */
            "mysql.char"             => SqlCharacterType::class,
            "mysql.varchar"          => SqlCharacterType::class,
            "mysql.binary"           => SqlCharacterType::class,
            "mysql.varbinary"        => SqlCharacterType::class,
            "mysql.tinyblob"         => SqlCharacterType::class,
            "mysql.blob"             => SqlCharacterType::class,
            "mysql.mediumblob"       => SqlCharacterType::class,
            "mysql.longblob"         => SqlCharacterType::class,
            "mysql.tinytext"         => SqlCharacterType::class,
            "mysql.text"             => SqlCharacterType::class,
            "mysql.mediumtext"       => SqlCharacterType::class,
            "mysql.longtext"         => SqlCharacterType::class,
            "mysql.enum"             => SqlCharacterType::class,
            "mysql.set"              => SqlCharacterType::class,
            
            "mssql.char"             => SqlCharacterType::class,
            "mssql.varchar"          => SqlCharacterType::class,
            "mssql.text"             => SqlCharacterType::class,
            "mssql.nchar"            => SqlCharacterType::class,
            "mssql.nvarchar"         => SqlCharacterType::class,
            "mssql.ntext"            => SqlCharacterType::class,
            "mssql.binary"           => SqlCharacterType::class,
            "mssql.varbinary"        => SqlCharacterType::class,
            "mssql.image"            => SqlCharacterType::class,
            
            "pgsql.character"        => SqlCharacterType::class,
            "pgsql.varying"          => SqlCharacterType::class,
            "pgsql.varchar"          => SqlCharacterType::class,
            "pgsql.char"             => SqlCharacterType::class,
            "pgsql.text"             => SqlCharacterType::class
        ];
    }
}

?>