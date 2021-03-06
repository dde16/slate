<?php


abstract class Str extends ScalarType {
    use \Slate\Utility\TMacroable;

    public const NAMES            = ["string", "str"];
    public const VALIDATOR        = "is_string";
    public const CONVERTER        = "strval";
    public const CONVERT_FORWARD  = [ \Slate\Data\IStringForwardConvertable::class, "toString" ];
    public const CONVERT_BACKWARD = [ \Slate\Data\IStringForwardConvertable::class, "fromString" ];

    public const ASCII_LOWERCASE = "abcdefghijklmnopqrstuvwxyz";
    public const ASCII_UPPERCASE = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    public const ASCII_LETTERS   = Str::ASCII_LOWERCASE . Str::ASCII_UPPERCASE;

    public const DIGITS   = "0123456789";

    public const HEX_LOWERCASE = \Str::DIGITS."abcdef";
    public const HEX_UPPERCASE = \Str::DIGITS."ABCDEF";

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

    
    public const CONTROL_CHARS_ESCAPE = [
        "\n" => "\\n",
        "\r" => "\\r",
        "\t" => "\\t",
        "\f" => "\\f",
        "\v" => "\\v",
        "\0" => "\\0"
    ];

    public static function match(string $string, string $pattern): bool {
        return fnmatch($pattern, $string);
    }

    public static function formatDateInterval(
        DateInterval $interval,
        array $names = [],
        array $ignoring = ["f"],
        string $unitDelimiter = "",
        string $listDelimiter = ", ",
        string $lastListDelimiter = " and ",
        int $largest = 3,
        bool $skipZeros = true,
    ) {
        $names = \Arr::map(
            \Arr::merge(
                [
                    "y" => "y",
                    "m" => "mo",
                    "d" => "d",
                    "h" => "h",
                    "i" => "m",
                    "s" => "s",
                    "f" => "ms"
                ],
                $names
            ),
            function(array|string $names) {
                if(is_string($names))
                    $names = [$names, $names];

                $names[1] = \Str::format($names[1] ?? "{singular}s", ["singular" => $names[0]]);

                return $names;
            }
        );

        $interval = \Arr::entries(\Arr::except(\Arr::only((array)$interval, ["y", "m", "d", "h", "i", "s", "f"]), $ignoring));

        $nonzero = fn($v) => $v[1] != 0;

        [$firstNonZeroIndex] = \Arr::firstEntry($interval, $nonzero);

        

        $interval = \Arr::slice($interval, $firstNonZeroIndex ?? 0, $largest);
        
        if($skipZeros)
            $interval = \Arr::filter($interval, $nonzero);


        $interval = \Arr::mapAssoc(
            $interval,
            function(int $index, array $entry) use($names, $unitDelimiter) {
                [$key, $value] = $entry;

                return [null, $value.$unitDelimiter.($names[$key][$value > 1])];
            }
        );

        return \Arr::join(\Arr::slice($interval, 0, -1), $listDelimiter) . ($lastListDelimiter ? $lastListDelimiter : $listDelimiter).\Arr::last($interval);
    }

    public static function fromByteArray(array $bytes) {
        return \Arr::map($bytes, fn(string|int $byte): string => is_int($byte) ? chr($byte) : $byte);
    }

    public static function toIntegers(string $bytes, int $bitsize = 8): array {
        return \Arr::map(
            \Str::split($bytes, $bitsize / 8),
            function(string $bytes): int {
                return \Integer::fromBytes($bytes);
            }
        );
    }

    public static function toBinary(string $bytes, int $bitsize = 8, string $separator = " "): string {
        return \Arr::join(
            \Arr::map(
                \Str::toIntegers($bytes, $bitsize),
                fn(int $integer): string => \Str::padLeft(decbin($integer), "0", $bitsize)
            ),
            $separator
        );
    }

    public static function escape(string $string, array $escape = ["\"", "'", "`", "\\"]): string {
        return preg_replace_callback(
            "/(\\\\)*(" . \Arr::join(\Arr::map($escape, Closure::fromCallable('preg_quote')), "|") . ")/",
            function($matches) {
                $match = $matches[2];
                $escapes = $matches[1];

                if(empty($escapes))
                    $escapes = "\\";

                if(strlen($escapes) % 2 === 0)
                    $escapes .= "\\";

                return $escapes.$match;
            },
            $string
        );
    }

    public static function fromSpaceTable(string $table) {
        $rows = \Arr::map(\Str::split($table, "\n"), fn(string $row): string => \Str::removeSuffix($row, "\r"));

        $header = $rows[0];
        $rows = \Arr::slice($rows, 1);
        $columns = [];

        if(preg_match_all("/(?'column'[^\ ]+(?:\ [^\ ]+)*)+/", $header, $columns, PREG_OFFSET_CAPTURE)) {
            $columns = $columns["column"];

            return \Arr::map(
                $rows,
                function(string $rowString) use($columns) {
                    $lastColumnName  = null;
                    $lastColumnStart = null;

                    $rowArray = [];

                    foreach($columns as list($nextColumnName, $nextColumnStart)) {
                        if($lastColumnName !== null && $lastColumnStart !== null) {
                            $rowArray[$lastColumnName] = \Str::trimSuffix(substr(
                                $rowString,
                                $lastColumnStart,
                                $nextColumnStart - $lastColumnStart
                            ), " ");
                        }

                        $lastColumnName = $nextColumnName;
                        $lastColumnStart = $nextColumnStart;
                    }

                    $rowArray[\Arr::last($columns)[0]] = \Str::trimSuffix(substr($rowString, \Arr::last($columns)[1]), " ");

                    return $rowArray;
                }
            );
        }
    }

    public static function replaceManyAt(string $string, array $withs): string {
        $mod = 0;

        foreach($withs as list($with, $from, $to)) {

            $from += $mod;
            $to   += $mod;

            $string = \Str::replaceAt($string, $with, $from, $to);

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
        return preg_match("/^[a-zA-Z0-9\/\r\n+]*={0,2}$/", $source);
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
            return ((\Str::len($source) % 2 === 0) ? (bool)preg_match("/^[a-fA-F0-9]+$/", $source) : false);
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
    public static function startswith(string $string, string|array $value): bool {
        return \Arr::any(
            !is_array($value) ? [$value] : $value,
            fn(string $value) => (substr(strval($string), 0, strlen($value)) === $value)
        );
    }

    /**
     * Check if a string ends with another string.
     *
     * @param source
     * @param value The value to check for in the source string.
     * @return bool
     */
    public static function endswith(string $source, string|array $value): bool {
        return \Arr::any(
            !is_array($value) ? [$value] : $value,
            fn(string $value) => (substr($source, strlen($source) - strlen($value), strlen($source)) === $value)
        );
    }

    /**
     * Convert a string to its character codes.
     *
     * @param string $source
     *
     * @return array
     */
    public static function ord(string $source): array {
        return \Arr::map(\Str::split($source), Closure::fromCallable('ord'));
    }

    public static function explode(string $string, string $delimiter = " ", int $limit = PHP_INT_MAX): array {
        return explode($delimiter, $string, $limit);
    }

    public static function join(array $pieces, string $glue = ""): string {
        return implode($glue, $pieces);
    }

    public static function split(string $source, int|string|array $splitter = 1): array {
        $length = \Str::len($source);
        $array = [];

        if (is_int($splitter)) {
            return $source !== "" ? str_split($source, $splitter) : [];
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
        if(\Arr::count($arguments) === 1 && is_array($arguments[0])) {
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

    public static function pluralise(string $string, string $suffix, int $count) {
        return \Str::format($string, [ "i" => $count ]) . ($count > 1 || $count === 0 ? $suffix : "");
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

    public static function divide(string $string): array {
        $centreOffset = strlen($string) / 2;
        $prefixOffset = $centreOffset;
        $suffixOffset = $centreOffset;
    
        $centre = "";
    
        if(\Math::mod($centreOffset, 1) != 0) {
            $centreOffset = intval($centreOffset);
            $centre = substr($string, intval($centreOffset), 1);
            $suffixOffset++;
        }
    
        $prefix = substr($string, 0, $prefixOffset);
        $suffix = substr($string, $suffixOffset);
    
        return [$prefix, $centre, $suffix];
    }

    public static function wrapc(string $source, string $wrapper): string {
        [$prefix, $centre, $suffix] = \Str::divide($wrapper);

        return $prefix.\Str::val($source).$centre.$suffix;
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

    
    public static function removeSuffixes(string $source, array $suffixes): string {
        foreach($suffixes as $index => $suffix)
            $source = \Str::removeSuffix($source, $suffix);

        return $source;
    }
    
    public static function trimSuffixes(string $source, array $suffixes): string {

        while (\Str::endswith($source, $suffixes)) {
            $source = \Str::removeSuffixes($source, $suffixes);
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

    /**
     * Replace control characters to their printable versions, if they have them.
     *
     * @param string $string
     * @return string
     */
    public static function controls(string $string): string {
        return Str::swap($string, \Str::CONTROL_CHARS_ESCAPE);
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
                    else if(($json = json_encode($any, JSON_PRETTY_PRINT)) !== false) {
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

    /**
     * Determine multiple common prefiexes amongst a collection of strings.
     * 
     * @param array $strings
     * 
     * @return array
     */
    public static function getPrefixes(array $strings, bool $relative = false): array {
        $lengths = \Arr::map($strings, Closure::fromCallable('strlen'));
    
        [$target, $length] = \Arr::maxEntry($lengths);
        $prefixes = [];
        $prefix = "";
        $lastMatches = 0;
    
        for($index = 0; $index < $length; $index++) {
            $char = $strings[$target][$index];
            $filtered = \Arr::filter($strings, fn(int $key): bool => $index <= $lengths[$key], \Arr::FILTER_KEY);
    
            if(count($filtered) > 0) {
                $matches = \Arr::count($filtered, fn(string $string): bool => $string[$index] === $char);
    
                if($matches < $lastMatches) {
                    $prefixes[] = $prefix;
    
                    if($relative)
                        $prefix = "";
                }
    
                if($matches > 1)
                    $prefix .= $char;
    
                if($matches === 1)
                    break;
    
                $lastMatches = $matches;
            }
        }
    
        return $prefixes;
    }

    /**
     * Determine a common prefix amongst a collection of strings.
     *
     * @param array $strings
     * @return string
     */
    public static function getPrefix(array $strings): ?string {
        $length = \Arr::min(\Arr::map($strings, Closure::fromCallable('strlen')));
        $prefix = null;

        for($index = 0; $index < $length; $index++) {
            $char = $strings[0][$index];

            if(\Arr::all(\Arr::slice($strings, 1), fn(string $string): bool => $string[$index] === $char)) {
                $prefix = ($prefix ?? "") . $char;
            }
            else {
                break;
            }
        }

        return $prefix ;
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

        foreach($replace as $from => $to)
            $last = \Str::replace($last, $from, $to);

        return $last;
    }

    /**
     * Convert a string to camel case.
     * 
     * @param string $source
     * @return string
     */
    public static function camel(string $source): string {
        return lcfirst(str_replace(" ", "", ucwords(str_replace(["-", "_"], " ", $source))));
    }

    /**
     * Conert a string to snake case.
     *
     * @param string $source
     * @return string
     */
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

    /**
     * Convert a string to title case.
     *
     * @param string $source
     * @return string
     */
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

    /**
     * Convert a string to kebab case.
     *
     * @param string $source
     * @return string
     */
    public static function kebab(string $source): string {
        preg_match_all("/[A-Z]{2,}(?=[A-Z][a-z]+[0-9]*|\b)|[A-Z]?[a-z]+[0-9]*|[A-Z]|[0-9]+/", $source, $matches);

        return \Arr::join(
            \Arr::map(
                $matches[0],
                fn(string $s): string => \Str::lower($s)
            ) ?? [],
            "-"
        );
    }
}

?>