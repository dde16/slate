<?php declare(strict_types = 1);

namespace Slate\Sql\Interpreter {
    use Slate\Data\Iterator\StringIterator;
    use Slate\Lang\Interpreter;
    use Slate\Lang\Interpreter\Attribute\ByteToken;
    use Slate\Lang\Interpreter\Attribute\ComplexToken;
    use Slate\Lang\Interpreter\Attribute\ComplexTokeniser;
    use Slate\Lang\Interpreter\Attribute\CompoundToken;
    use Slate\Lang\Interpreter\Attribute\ControlsToken;
    use Slate\Lang\Interpreter\Attribute\LiteralToken;
    use Slate\Lang\Interpreter\Attribute\DigitToken;
    use Slate\Lang\Interpreter\Trait\TFloatTokeniser;
    use \Generator;

    class MySqlWellKnownTextInterpreter extends Interpreter {
        use TFloatTokeniser {
            TFloatTokeniser::floatTokeniser as _floatTokeniser;
        }
    
        #[LiteralToken("Point", exact: false)]
        public const TOKEN_POINT           = (1<<0);
    
        #[LiteralToken("MultiPoint", exact: false)]
        public const TOKEN_MULTIPOINT      = (1<<1);
    
        #[LiteralToken("LineString", exact: false)]
        public const TOKEN_LINESTRING      = (1<<2);
    
        #[LiteralToken("MultiLineString", exact: false)]
        public const TOKEN_MULTILINESTRING = (1<<3);
    
        #[LiteralToken("Polygon", exact: false)]
        public const TOKEN_POLYGON         = (1<<4);
    
        #[LiteralToken("MultiPolygon", exact: false)]
        public const TOKEN_MULTIPOLYGON    = (1<<5);
    
        #[LiteralToken("GeometryCollection", exact: false)]
        public const TOKEN_GEOMETRYCOLLECTION = (1<<6);
    
        #[ComplexToken]
        public const TOKEN_FLOAT           = (1<<7);
    
        #[LiteralToken('(')]
        public const TOKEN_LEFT_BRACKET    = (1<<8);
    
        #[LiteralToken(')')]
        public const TOKEN_RIGHT_BRACKET   = (1<<9);
    
        #[LiteralToken(' ')]
        public const TOKEN_SPACE           = (1<<10);
        
        #[LiteralToken(',')]
        public const TOKEN_COMMA           = (1<<11);
    
        #[LiteralToken('.')]
        public const TOKEN_DOT             = (1<<12);
    
        #[DigitToken]
        public const TOKEN_DIGIT           = (1<<13);
    
        /** Control chars */
        #[ControlsToken]
        public const TOKEN_CONTROLS        = (1<<14);
    
        #[ComplexTokeniser(self::TOKEN_FLOAT)]
        public function floatTokeniser(): ?array {
            return $this->_floatTokeniser(static::TOKEN_DIGIT, static::TOKEN_DOT);
        }
    
        public function expectPoint(): ?array {
            $prefixed = $this->matchToken(static::TOKEN_POINT);
            $bracketed = $this->matchToken(static::TOKEN_LEFT_BRACKET, raise: $prefixed !== null);
    
            if (!($x = $this->matchToken(static::TOKEN_FLOAT))) {
                return null;
            }
    
            $this->matchToken(static::TOKEN_SPACE, raise: true);
    
            $y = $this->matchToken(static::TOKEN_FLOAT, raise: true);
    
            if ($bracketed) {
                $this->matchToken(static::TOKEN_RIGHT_BRACKET, raise: true, raiseEof: true);
            }
    
            return [
                "POINT", 
                [
                    $x,
                    $y
                ]
            ];
        }
    
        public function expectPointList(): Generator {
            return $this->expectList(
                fn(): ?array => $this->expectPoint(static::TOKEN_FLOAT),
                static::TOKEN_COMMA
            );
        }
    
        public function expectWrappedPointList(): Generator {
            $this->matchToken(static::TOKEN_LEFT_BRACKET, raise: true, raiseEof: true);
    
            foreach ($this->expectPointList() as $point) {
                yield $point;
            }
    
            $this->matchToken(static::TOKEN_RIGHT_BRACKET, raise: true, raiseEof: true);
        }
    
        public function expectMultiPoint(bool $raise = true, bool $raiseEof = true): ?array {
            if (!$this->matchToken(static::TOKEN_MULTIPOINT, raise: $raise, raiseEof: $raiseEof)) {
                return null;
            }
    
            return [
                "MULTIPOINT",
                $this->expectWrappedPointList()
            ];
        }
    
        public function expectLineString(bool $raise = true, bool $raiseEof = true): ?array {
            if (!$this->matchToken(static::TOKEN_LINESTRING, raise: $raise, raiseEof: $raiseEof)) {
                return null;
            }
    
            return [
                "LINESTRING",
                $this->expectWrappedPointList()
            ];
        }
    
        public function expectLineStringList(): Generator {
            return $this->expectList(
                fn(): ?array => $this->expectLineString(),
                static::TOKEN_COMMA
            );
        }
    
        public function expectWrappedLineStringList(): Generator {
            return $this->expectBetween(
                fn(): Generator => $this->expectLineStringList(),
                static::TOKEN_LEFT_BRACKET,
                static::TOKEN_RIGHT_BRACKET
            );
        }
    
        public function expectMultiLineString(bool $raise = true, bool $raiseEof = true): ?array {
            if (!$this->matchToken(static::TOKEN_MULTILINESTRING, raise: $raise, raiseEof: $raiseEof)) {
                return null;
            }
    
            return [
                "MULTILINESTRING", 
                $this->expectWrappedLineStringList()
            ];
        }
    
        public function expectPolygon(bool $raise = true, bool $raiseEof = true): ?array {
            if (!$this->matchToken(static::TOKEN_POLYGON, raise: $raise, raiseEof: $raiseEof)) {
                return null;
            }
    
            return [
                "POLYGON", 
                $this->expectWrappedLineStringList()
            ];
        }
    
        public function expectPolygonList(): Generator {
            return $this->expectList(
                fn(): ?array => $this->expectPolygon(),
                static::TOKEN_COMMA
            );
        }
    
        public function expectWrappedPolygonList(): Generator {
            return $this->expectBetween(
                fn(): Generator => $this->expectPolygonList(),
                static::TOKEN_LEFT_BRACKET,
                static::TOKEN_RIGHT_BRACKET
            );
        }
    
        public function expectMultiPolygon(bool $raise = true, bool $raiseEof = true): ?array {
            if (!$this->matchToken(static::TOKEN_MULTIPOLYGON, raise: $raise, raiseEof: $raiseEof)) {
                return null;
            }
    
            return [
                "MULTIPOLYGON", 
                $this->expectWrappedPolygonList()
            ];
        }
    
        public function expectWrappedGeometryList(): Generator {
            return $this->expectBetween(
                fn(): Generator => $this->expectGeometryList(),
                static::TOKEN_LEFT_BRACKET,
                static::TOKEN_RIGHT_BRACKET
            );
        }
    
        public function expectGeometryList(): Generator {
            return $this->expectList(
                fn(): ?array => (
                    $this->expectPoint()
                    ?? $this->expectMultiPoint(raise: false)
                    ?? $this->expectPolygon(raise: false)
                    ?? $this->expectLineString(raise: false)
                    ?? $this->expectMultiPolygon(raise: false)
                    ?? $this->expectMultiLineString(raise: false)
                ),
                static::TOKEN_COMMA
            );
        }
    
        public function expectGeometryCollection(bool $raise = true, bool $raiseEof = true): ?array {
            if (!$this->matchToken(static::TOKEN_GEOMETRYCOLLECTION, raise: $raise, raiseEof: $raiseEof)) {
                return null;
            }
    
            return [
                "GEOMETRYCOLLECTION",
                $this->expectWrappedGeometryList()
            ];
        }
        
        public function parse(): Generator {
            yield
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