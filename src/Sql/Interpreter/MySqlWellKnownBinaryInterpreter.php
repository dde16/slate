<?php declare(strict_types = 1);

namespace Slate\Sql\Interpreter {

    use RuntimeException;
    use Slate\Data\Iterator\IAnchoredIterator;
    use Slate\IO\Contract\IStreamReadable;
    use Slate\Utility\Singleton;

    class MySqlWellKnownBinaryInterpreter extends Singleton {
        public const LITTLE_ENDIAN           = 0x00;
        public const BIG_ENDIAN              = 0x01;

        public const TYPE_POINT              = 0x01;
        public const TYPE_LINESTRING         = 0x02;
        public const TYPE_POLYGON            = 0x03;
        public const TYPE_MULTIPOINT         = 0x04;
        public const TYPE_MULTILINESTRING    = 0x05;
        public const TYPE_MULTIPOLYGON       = 0x06;
        public const TYPE_GEOMETRYCOLLECTION = 0x07;

        public const TYPES = [
            self::TYPE_POINT,
            self::TYPE_LINESTRING,
            self::TYPE_POLYGON,
            self::TYPE_MULTIPOINT,
            self::TYPE_MULTILINESTRING,
            self::TYPE_MULTIPOLYGON,
            self::TYPE_GEOMETRYCOLLECTION
        ];

        protected IAnchoredIterator&IStreamReadable $code;
        protected int   $endianness;

        public function expectDouble(): string {
            $value = $this->code->read(8, true);

            if($value === null)
                throw new RuntimeException("Unexpected EOF.");

            return $value;
        }

        public function expectType(int $assert): int {
            $this->code->anchor();

            $type = $this->code->read(4, true);

            if($type === null)
                throw new RuntimeException("Unexpected EOF.");

            $type = \Integer::fromBytes($type);

            if(!\Arr::contains(static::TYPES, $type))
                throw new RuntimeException("Unknown type '{$type}'.");


            return $type;
        }

        public function expectPoint() {
            
        }

        public function parse(IAnchoredIterator&IStreamReadable $code) {
            $this->code = $code;

            $this->endianness = \Integer::fromBytes($this->code->current());

            if($this->endianness !== static::LITTLE_ENDIAN && $this->endianness !== static::BIG_ENDIAN)
                throw new RuntimeException("Invalid endian value '{$this->endianness}' at byte 0.");

            return
                $this->expectPoint()
                ?? $this->expectMultiPoint(raise: false)
                ?? $this->expectPolygon(raise: false)
                ?? $this->expectLineString(raise: false)
                ?? $this->expectMultiPolygon(raise: false)
                ?? $this->expectMultiLineString(raise: false)
                ?? $this->expectGeometryCollection(raise: false, raiseEof: true)
            ;
        }

    }
}

?>