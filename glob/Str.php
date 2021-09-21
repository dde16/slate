<?php

use Slate\Interface\IStringForwardConvertable;
use Slate\Interface\IStringBackwardConvertable;

abstract class Str extends ScalarType {
    public const NAMES            = ["string", "str"];
    public const VALIDATOR        = "is_string";
    public const CONVERTER        = "strval";
    public const CONVERT_FORWARD  = [ IStringForwardConvertable::class, "toString" ];
    public const CONVERT_BACKWARD = [ IStringForwardConvertable::class, "fromString" ];

    public const ASCII_LOWERCASE = "abcdefghijklmnopqrstuvwxyz";
    public const ASCII_UPPERCASE = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    public const ASCII_LETTERS   = Str::ASCII_LOWERCASE . Str::ASCII_UPPERCASE;

    public const DIGITS   = "0123456789";

    public const FORMAT_ESCAPE_PATTERN =  "(?<!\\\\)";
    public const FORMAT_KEY_PATTERN = "[\w\d\-\_\=\+\:\.]*";

    public const FORMAT_PATTERN =
        "/" .
        \Str::FORMAT_ESCAPE_PATTERN .
        "{(?'key'" .
        \Str::FORMAT_KEY_PATTERN .
        ")" .
        \Str::FORMAT_ESCAPE_PATTERN .
        "}/";

    public const PERCENTAGE_PATTERN = "(?'number'(?:[0-9]?[0-9](?:\.(?'decimal'[0-9]+))?)|100)%";

    public const PAD_LEFT = STR_PAD_LEFT;
    public const PAD_RIGHT = STR_PAD_RIGHT;

    public static function replaceManyAt(string $string, array $withs): string {
        $mod = 0;

        foreach($withs as list($with, $from, $to)) {

            $from += $mod;
            $to   += $mod;

            $string = \Str::replaceAt(
                $string,
                $with,
                $from,
                $to
            );

            $substrLength = ($to - $from);
            $withLength   = strlen($with);

            $mod += $withLength - $substrLength;
        }

        return $string;
    }

    public static function replaceAt(string $string, string $with, int $from, int $to): string {
        $len = $to - $from;

        if($len < 0)
            throw new Error("Invalid replace positions, start is larger than end.");

        $left = substr($string, 0, $from);
        $right = substr($string, $to);

        return "$left$with$right";
    }

    public static function isUpper(string $char): bool {
        return \Str::isChar($char) ? IntlChar::isUpper($char) : false;
    }

    public static function isLower(string $char): bool {
        return \Str::isChar($char) ? IntlChar::isLower($char) : false;
    }

    public static function reverse(string $source): string {
        return strrev($source);
    }

    public static function afterFirst(string $source, string $splitter): string {
        return (($where = \Str::first($source, $splitter)) !== -1) ? \Str::slice(
            $source,
            $where + strlen($splitter)
        ) : $source;
    }

    public static function afterLast(string $source, string $splitter): string {
        return (($where = \Str::last($source, $splitter)) !== -1) ? \Str::slice(
            $source,
            ($where + strlen($splitter))
        ) : $source;
    }

    public static function beforeFirst(string $source, string $splitter): string {
        return (($pos = \Str::first($source, $splitter)) !== -1 ? \Str::slice($source, 0, $pos) : $source);
    }

    public static function beforeLast(string $source, string $splitter): string {
        return (($pos = \Str::last($source, $splitter)) !== -1 ? \Str::slice($source, 0, $pos) : $source);
    }

    public static function first(string $haystack, string $needle, int $offset = 0, bool $sensitive = true): int {
        return ($pos = ($sensitive ? 'strpos' : 'stripos')($haystack, $needle, $offset)) !== false ? $pos : -1;
    }

    public static function last(string $haystack, string $needle, int $offset = 0, bool $sensitive = true): int {
        return ($pos = ($sensitive ? 'strrpos' : 'strripos')($haystack, $needle, $offset)) !== false ? $pos : -1;
    }

    public static function slice(string $string, int $offset, int $length = null): string {
        return substr($string, $offset, $length);
    }

    public static function trimcode(string $source, string $ignore = "/(?:(\"(?:[^\"]|\")*\")|('(?:[^']|\\\\')*'))/"): array {
        if(preg_match_all($ignore, $source, $ignoring, PREG_OFFSET_CAPTURE)) {
            $ignoring = \Arr::column(\Arr::map(
                $ignoring[0],
                function($match) {
                    list($match, $start) = $match;
                    $length = strlen($match);

                    return [$start, $start + $length];
                }
            ), 0, 1);

            $count = null;

            return preg_replace_callback("/\s+/", function($match) use($ignoring) {
                $match = $match[0];
                $any = \Arr::any(\Arr::entries($ignoring), function($pos) use($match) {return $pos[0] <= $match[1] &&  $pos[1] > $match[1]; });

                return $any ? $match[0] : "";
            }, $source, -1, $count, PREG_OFFSET_CAPTURE);
        }

        return $source;
    }

    public static  function hashcode(string $string, int $bitsize = 61): int {
        $hashcode = 0;
        $length = strlen($string);

        for($index = 0; $index < $length; $index++) {
            $hashcode = \Integer::overflow(
                ((($bitsize - 1) * $hashcode + ord($string[$index]))),
                $bitsize
            );

            if($hashcode < 0) $hashcode *= -1;
        }

        return $hashcode;
    }

    public static function abbreviate(string $string, int $size = -1): string {
        $words = \Arr::values(
            \Arr::filter(
                \Arr::map(
                    preg_split("/[\ -]/", $string),
                    function($substring) {
                        return ctype_upper(substr($substring, 0, 1)) ? \Str::uppercase($substring) : null;
                    }
                )
            )
        );
    
        $wordsCount = count($words);
    
        return \Arr::join(
            \Arr::mapAssoc(
                $words,
                function($key, $value) use($size, $wordsCount) { 
                    $key = intval($key);
    
                    if($key === $wordsCount-1 && $size - $key > 1)
                        return [$key, substr($value, 0, $size - $key)];
    
                    return [$key, substr($value, 0, 1)];
                }
            )
        );
    }

    
    /**
     * Chunk a string by a given size.
     * 
     * @param array $array
     * @param int   $interval
     */
    public static function chunk(string $string, int $chunksize): Generator {
        for($index = 0; $index < strlen($string); $index++) {
            if($index % $chunksize === 0)
                yield substr($string, $index, $chunksize);
        }
    }

    public static function start(string $string): string {
        return substr($string, 0, 1);
    }

    public static function end(string $string): string {
        return substr($string, strlen($string) - 1);
    }

    public static function isIpAddress($ipAddress): bool {
        return is_string($ipAddress) ? filter_var($ipAddress, FILTER_VALIDATE_IP) : false;
    }

    public static function isPath(string $path): bool {
        return preg_match(\Path::PATTERN, $path);
    }

    public static function len(string $source): int {
        return strlen($source);
    }

    public static function affixedwith(string $source, string $affix): bool {

        return strlen($source) > 1 ? (\Str::startswith($source, $affix) && \Str::endswith($source, $affix)) : false;
    }

    public static function wrappedwith(string $source, string $wrapper): bool {
        $wrapper = \Str::split($wrapper);
        $middle = \Arr::middle($wrapper);
        $prefix = \Str::join(\Arr::slice($wrapper, 0, $middle));
        $suffix = \Str::join(\Arr::slice($wrapper, $middle));

        return \Str::startswith($source, $prefix) && \Str::endswith($source, $suffix);
    }

    /**
     * Used to check whether a string is base64 data.
     *
     * @param string $source The string to be tested.
     * @return bool
     */
    public static function isBase64(string $source): bool {
        return (bool)\Str::match("/^[a-zA-Z0-9\/\r\n+]*={0,2}$/", $source);
    }

    //TODO: review use and remove
    public static function match(string $pattern, string $source, array &$matches = null): bool {
        return preg_match($pattern, $source, $matches);
    }

    public static function isChar($char): bool {
        return is_string($char) ? strlen($char) === 1 : false;
    }

    /**
     * Used to check whether a string is hex data.
     *
     * @param string $source The string to be tested.
     * @return bool
     */
    public static function isHex(string $source): bool {
        if ($source !== NULL) {
            return ((\Str::len($source) % 2 === 0) ? (bool)\Str::match("/^[a-fA-F0-9]+$/", $source) : false);
        }

        return false;
    }

    public static function count(string $string, string $substring): int {
        return substr_count($string, $substring);
    }

    public static function isDotlink(string $dotlink): bool {
        return ($dotlink === "." || $dotlink === "..");
    }

    public static function isEmpty(string $source): bool {
        return (\Str::len(\Str::trim($source)) === 0);
    }

    /**
     * Check if a string starts with another string.
     *
     * @param string $string
     * @param string $value The value to check for in the source string.
     * @return bool
     */
    public static function startswith(string $string, string $value): bool {
        return (substr(strval($string), 0, strlen($value)) === $value);
    }

    /**
     * Check if a string ends with another string.
     *
     * @param source
     * @param value The value to check for in the source string.
     * @return bool
     */
    public static function endswith(string $source, string $value): bool {
        return (substr($source, strlen($source) - strlen($value), strlen($source)) === $value);
    }

    public static function divide(string $source): array {
        $source = \Str::split($source);
        $middle = \Arr::middle($source);
        $prefix = \Str::join(\Arr::slice($source, 0, $middle));
        $suffix = \Str::join(\Arr::slice($source, $middle));

        return [$prefix, $suffix];
    }

    public static function ord(string $source): array {
        return \Arr::map(
            \Str::split($source),
            function ($char) {
                return ord($char);
            }
        );
    }

    //TODO: review usage and remove
    public static function explode(string $string, string $delimiter = " ", int $limit = PHP_INT_MAX): array {
        return explode($delimiter, $string, $limit);
    }

    public static function join(array $pieces, string $glue = ""): string {
        return \Str::implode($pieces, $glue);
    }

    //TODO: review usage and remove
    public static function implode(array $pieces, string $glue = ""): string {
        return implode($glue, $pieces);
    }

    public static function split(string $source, int|string|array $splitter = 1): array {
        $length = \Str::len($source);
        $array = [];

        if (is_int($splitter)) {
            return str_split($source, $splitter);

            // for($index = 0; $index < $length; $index++) {
            //     if ($index % $splitter === 0) {
            //         $substring = substr($source, $index, $splitter);
            //         $array[] = $substring;
            //     }
            // }
        }
        else if(is_string($splitter)) {
            $array = \Str::explode($source, $splitter);
        }
        else if(is_array($splitter)){ 
            $splitter = \Arr::sort($splitter);
            $strings = [];

            if(\Arr::first($splitter) !== 0) {
                $splitter = \Arr::merge([0], $splitter);
            }

            if(\Arr::last($splitter) !== $length) {
                $splitter[] = $length;
            }

            $splitter = \Arr::lead($splitter);

            foreach($splitter as $group) {
                // if($group[0] !== $group[1]) {
                $strings[] = \Str::substring($source, $group[0], $group[1]);
                // }

                
            }

            return $strings;
        }
        else {
            throw new \InvalidArgumentException();
        }

        return $array;
    }

    /**
     * Format a string using 'parts'.
     *
     * @param string $format Format in the form of; /{{\s*([\w]+)\s*}}/g
     * @param array $parts Array of the parts to be used in formatting, keyed or not.
     * @return string
     */
    public static function format(string $format, ...$arguments): string {
        return \Str::formatwith($format, "/(?<!\\\\){(?'key'[\w\d\-\_\.]*)(?<!\\\\)}/", $arguments);
    }

    public static function restformat(string $format, ...$arguments): string {
        return \Str::formatwith($format, "/:(?'key'[\w\d\-\_\.]+)/", $arguments);
    }

    public static function formatwith(string $format, string $with, array $arguments): string {
        if(\Arr::count($arguments) === 1 && \Any::isArray($arguments[0])) {
            $arguments      = $arguments[0];
        }

        $matches = [];
        $index = 0;

        $intermediate = preg_replace_callback(
            $with,
            function($matches) use($arguments, &$index) {
                $value = $matches[0];
                $name = $matches["key"];

                if(\Arr::hasKey($arguments, $name)) {
                    $value = $arguments[$name];
                }
                else if(\Arr::hasKey($arguments, $index)) {
                    $value = $arguments[$index];
                }

                $index++;

                return $value;
            },
            $format
        );

        if($intermediate !== NULL) {
            return $intermediate;
        }

        return $format;
    }

    public static function extract(string $string, string $format, string $pattern = "[[:print:]]+"): array {
        $index = 0;
        $extract  = [];

        $regex = "/^" . preg_replace_callback(
            \Str::FORMAT_PATTERN,
            function($matches) use(&$index, &$extract, $pattern) {
                $value = $matches[0];
                $key = $matches["key"];

                if(\Str::isEmpty($key)) {
                    $key = strval($index);
                }
                
                $index++;

                if(\Arr::hasKey($extract, $key)) {
                    throw new Error(
                        \Str::format(
                            "Duplicate key '{}'.",
                            $key
                        )
                    );
                }

                $extract[$key] = null;

                return "(?'key_$key'" . $pattern . ")";
            },
            $format
        ) . "$/";

        $matches = [];

        if(preg_match($regex, $string, $matches)) {
            $extract = \Arr::mapAssoc(
                $extract,
                function($key, $_) use(&$matches) {
                    return [$key, $matches["key_".$key]];
                }
            );
        }

        return $extract;
    }

    public static function find(string $haystack, string $needle, int $offset = 0): int|false {
        return strpos($haystack, $needle);
    }

    public static function contains(string $string, string|array $substring): bool {
        if(!is_array($substring))
            $substring = [$substring];

        return \Arr::all($substring, function($substring) use($string) {
            return substr_count($string, $substring) > 0;
        });
    }

    public static function repeat(string $element, int $times): string {
        $string = "";

        for ($i = 0; $i < $times; $i++) {
            $string .= $element;
        }

        return $string;
    }

    public static function padLeft(string $string, string $pad, int $length): string {
        return \Str::pad($string, $pad, $length, \Str::PAD_LEFT);
    }

    public static function padRight(string $string, string $pad, int $length): string {
        return \Str::pad($string, $pad, $length, \Str::PAD_RIGHT);
    }

    public static function pad(string $string, string $pad, int $length, int $type = \Str::PAD_RIGHT): string {
        return str_pad($string, $length, $pad, $type);
    }

    public static function wrapc(string $source, string $wrapper): string {
        $wrapper = \Str::split($wrapper);
        $middle = \Arr::middle($wrapper);
        $prefix = \Str::join(\Arr::slice($wrapper, 0, $middle));
        $suffix = \Str::join(\Arr::slice($wrapper, $middle));

        return \Str::val($prefix) . \Str::val($source) . \Str::val($suffix);
    }

    public static function quote(string $source): string {
        return \Str::wrap($source, "'");
    }

    public static function addPrefix(string $source, string $prefix): string {
        if(!\Str::startswith($source, $prefix)) {
            return $prefix.$source;
        }

        return $source;
    }

    public static function addSuffix(string $source, string $suffix): string {
        if(!\Str::endswith($source, $suffix)) {
            return $source.$suffix;
        }

        return $source;
    }

    public static function removeAffix(string $source, string $affix): string {

        return \Str::affixedwith($source, $affix) ? \Str::removeSuffix(
            \Str::removePrefix(
                $source, $affix
            ),
            $affix
        ) : $source;
    }

    public static function trimAffix(string $source, string $affix): string {
        return \Str::trimSuffix(
            \Str::trimPrefix(
                $source,
                $affix
            ),
            $affix
        );
    }

    public static function trimPrefix(string $source, string $prefix): string {
        $prefixLength = \Str::len($prefix);

        while (\Str::startswith($source, $prefix)) {
            $source =
                substr($source, $prefixLength);
        }

        return $source;
    }

    public static function trimSuffix(string $source, string $suffix): string {
        $suffixLength = \Str::len($suffix);

        while (\Str::endswith($source, $suffix)) {
            $source = substr(
                $source,
                0,
                \Str::len($source) - $suffixLength
            );
        }

        return $source;
    }

    public static function removePrefixes(string $source, array $prefixes): string {
        foreach($prefixes as $index => $prefix) {
            $source = \Str::removePrefix($source, $prefix);
        }

        return $source;
    }

    public static function removePrefix(string $source, string $prefix): string {
        if(\Str::startswith($source, $prefix)) {
            return substr($source, \Str::len($prefix));
        }

        return $source;
    }

    public static function removeSuffix(string $source, string $suffix): string {
        if (\Str::endswith($source, $suffix)) {
            return substr(
                $source,
                0,
                \Str::len($source) - \Str::len($suffix)
            );
        }

        return $source;
    }

    /**
     * Wrap a string in characters.
     *
     * @param $inner The string to be wrapped.
     * @param $outer The outer string to wrap around the inner.
     * @return string
     */
    public static function wrap(string $inner, string $outer): string {
        return strval($outer) . strval($inner) . strval($outer);
    }

    public static function trim(string $source): string {
        return trim($source);
    }

    public static function repr($any): string {
        $value = "null";

        if($any !== NULL) {
            $type = \Any::getType($any, tokenise: true);

            switch($type) {
                case \Type::ARRAY:
                case \Type::OBJECT:
                    if(is_object($any) ? \Cls::hasInterface($any, \Slate\Data\IStringForwardConvertable::class) : false) {
                        $value = $any->toString();
                    }
                    else if(($json = \Json::encode($any, JSON_PRETTY_PRINT)) !== false) {
                        $value = $json;
                    }
                    else {
                        $value = gettype($any);
                    }
                    break;
                case \Type::BOOL:
                    $value = ($any) ? "true" : "false";
                    break;
                case \Type::INT:
                    $value = number_format($any, 0, '', '');
                    break;
                case \Type::STRING:
                    $value = $any;
                    break;
                case \Type::FLOAT:
                case \Type::DOUBLE:
                    $value = \Str::trimSuffix(\Str::trimSuffix(number_format($any, 10, '.', ''), "0"), ".");
                    break;
                default:
                    $value = gettype($any);
                    break;
            }
        }

        return strval($value);
    }

    public static function val($any): string {
        $value = "null";

        if($any !== null) {
            $type = \Any::getType($any, tokenise: true);

            switch($type) {
                case \Type::OBJECT:
                    $value = get_class($any);
                    break;
                case \Type::ARRAY:
                    $value = \Str::wrapc(\Str::join(
                        \Arr::map(
                            $any,
                            function ($value) {
                                return \Any::getType($value);
                            }
                        ),
                        ", "
                    ), "[]");
                    break;
                case \Type::INT:
                    $value = number_format($any, 0, '', '');
                    break;
                case \Type::BOOL:
                    $value = ($any) ? "true" : "false";
                    break;
                case \Type::STRING:
                    $value = $any;
                    break;
                case \Type::DOUBLE:
                case \Type::FLOAT:
                    $value = \Str::trimSuffix(\Str::trimSuffix(number_format($any, 10, '.', ''), "0"), ".");
                    // $value = strval((\Math::mod($any, 1.0) === 0.0 ? $any . ".0" : $any));
                    break;
                default:
                    $value = gettype($any);
                    break;
            }
        }

        return strval($value);
    }

    public static function tokenise(string $string, array $tokens, callable $callback = null): array {
        return static::tokenize($string, $tokens, $callback);
    }

    public static function tokenize(string $string, array $tokens, callable $callback = null): array {
        $pattern = \Str::wrapc(
            \Arr::join(
                \Arr::map(
                    \Arr::keys($tokens),
                    'preg_quote'
                ),
                "|"
            ),
            "/()/"
        );

        return \Arr::values(\Arr::map(
            \Arr::filter(preg_split(
                $pattern, $string,
                -1,
                PREG_SPLIT_DELIM_CAPTURE
            ), \Fnc::not('is_empty')),
            function($value) use ($tokens, $callback) {
                $token = $tokens[$value];

                if($token !== NULL) return $callback !== null ? $callback($token) : $token;

                return $value;
            }
        ));
    }

    //TODO: review usage and remove
    public static function encode(string $source, string $method): string {
        switch($method) {
            case "hex":
                return \Hex::encode($source);
                break;
            case "base64":
                return base64_encode($source);
                break;
            default:
                throw new \Error();
                break;
        }
    }

    public static function getPrefix(array $strings): array {
        $length = \Arr::min(\Arr::map($strings, Closure::fromCallable('strlen')));
        $prefix = "";

        for($index = 0; $index < $length; $index++) {
            $char = $strings[0][$index];

            if(\Arr::all(\Arr::slice($strings, 1), function($string) use($index, $char) { return $string[$index] === $char; })) {
                $prefix .= $char;
            }
            else {
                break;
            }
        }

        return [
            $prefix,
            \Arr::map(
                $strings,
                function($string) use($prefix) {
                    return !\Str::isEmpty($string) ? \Str::removePrefix($string, $prefix) : $string;
                }
            )
        ];
    }

    public static function upper(string $source): string {
        return strtoupper($source);
    }

    public static function uppercase(string $source): string {
        return strtoupper($source);
    }

    public static function lower(string $source): string {
        return strtolower($source);
    }

    public static function lowercase(string $source): string {
        return strtolower($source);
    }
    
    public static function substring(string $source, int $start, int $end): string {
        $length = $end - $start;

        return \Str::substr($source, $start, $length);
    }

    public static function substr(string $source, int $start, int $length = NULL): string {
        return substr($source, $start, $length);
    }

    public static function replace(array|string $subject, array|string $search, array|string $replace, int $count = null): array|string {
        return str_replace($search, $replace, $subject, $count);
    }

    public static function swap(string $source, array $replace): string {
        $last = $source;

        foreach ($replace as $from => $to)
            $last = \Str::replace($last, $from, $to);

        return $last;
    }

    public static function snake(string $source): string {
        return \Str::join(
            \Arr::map(
                \Str::split(
                    \Str::replace(
                        $source,
                        "-", ""
                    ),
                    " "
                ),
                "strtolower"
            ), "_"
        );
    }

    public static function title(string $source): string {
        return \Str::join(
            \Arr::map(
                \Str::split(
                    \Str::replace(
                        $source,
                        "_", " "
                    ),
                    " "
                ),
                \Closure::fromCallable('ucfirst')
            ), " "
        );
    }
}

?>