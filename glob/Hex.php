<?php

abstract class Hex {
    const TEXT    = 1;
    const BINARY  = 2;
    const DECIMAL = 4;

    const DIGITS   = 1;
    const STRING   = 2;

    public static function subtract(string ...$operands): array {
        $operands = \Arr::map(
            function($hex) {
                return \Hex::decode(
                    \Str::trimPrefix(
                        $hex,
                        "#"
                    ), \Hex::DECIMAL);
            },
            $operands
        );

        return $operands;
    }


    public static function toText(string $hex, int $bitsize = 8): string {
        $nibblesize = $bitsize / 2;

        return \Arr::join(\Arr::map(
            \Str::split(\Str::trim($hex), $nibblesize),
            function($hex) {
                return chr(hexdec($hex));
            }
        ));
    }
    
    public static function toBinary(string $hex): string {
        return hex2bin($hex);
    }

    public static function toDecimal(string $hex): int {
        return hexdec($hex);
    }
    
    public static function toDigits(string $digits, int $bitsize = 8): array {
        $nibblesize = $bitsize / 2;
        $charsize   = $nibblesize / 2;

        return \Arr::map(\Str::split(\Str::trim($digits), $charsize), 'hexdec');
    }
    
    public static function fromText(string $text): string {
        return \Arr::join(\Arr::map(
            \Str::split($text, 1),
            function($char) {
                return dechex(ord($char));
            }
        ));
    }

    public static function fromBinary(string $binary): string {
        return bin2hex($binary);
    }

    public static function fromDecimal(int $decimal): string {
        return dechex($decimal);
    }

    public static function decode(string $hex): string {
        return static::toBinary($hex);
    }

    public static function encode(string $binary): string{
        return static::fromBinary($binary);
    }
}

?>