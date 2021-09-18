<?php


abstract class Json {
    public static function encode(mixed $source, int $options = 0, int $depth = 512): string|false {
        return json_encode($source, $options, $depth);
    }

    public static function decode(string $source, bool $assoc = true, int $depth = 512, int $flags = 0): array|object|false {
        return json_decode($source, $assoc, $depth, $flags);
    }
    
    public static function parse(string $json, bool $assoc = true, int $depth = 512, int $flags = 0): array {
        $json = json_decode($json, $assoc, $depth, $flags);

        $hasError = (json_last_error() !== JSON_ERROR_NONE);

        return [
            $json,
            $hasError ? new Error(json_last_error_msg() . " while parsing json.", json_last_error()) : null 
        ];
    }

    public static function tryparse(string $json, bool $assoc = true, int $depth = 512, int $flags = 0): mixed {
        list($json, $error) = \Json::parse($json, $assoc, $depth, $flags);

        if($error !== null)
            throw $error;

        return $json;
    }

    public static function isValid(string $source): bool {
        $pattern = '
        /
        (?(DEFINE)
           (?<number>   -? (?= [1-9]|0(?!\d) ) \d+ (\.\d+)? ([eE] [+-]? \d+)? )    
           (?<boolean>   true | false | null )
           (?<string>    " ([^"\\\\]* | \\\\ ["\\\\bfnrt\/] | \\\\ u [0-9a-f]{4} )* " )
           (?<array>     \[  (?:  (?&json)  (?: , (?&json)  )*  )?  \s* \] )
           (?<pair>      \s* (?&string) \s* : (?&json)  )
           (?<object>    \{  (?:  (?&pair)  (?: , (?&pair)  )*  )?  \s* \} )
           (?<json>   \s* (?: (?&number) | (?&boolean) | (?&string) | (?&array) | (?&object) ) \s* )
        )
        \A (?&json) \Z
        /six   
      ';
        return preg_match($pattern, $source);
    }

    /**
     * This is used to determine the full scope of a json string from
     * a given start position. This is useful for web scraping for complex
     * web pages.
     *
     * @param string $source The contents of the string to climb.
     * @param int $position Where to start in the contents.
     * @param int $height The amount of [] / {} to climb out of.
     * @return array Contains the climbed json.
     */
    public static function climb(string &$source, int $pos, int $height, bool $assoc = true): array|object|null {
        $length   = \Str::len($source);
        $ladder   = 0;
        $start    = 0;
        $end      = 0;

        $ptr      = $pos;

        while($ptr !== 0) {
            $char = \Str::substr($source, $ptr, 1);

            if($char == "{" || $char == "[") {
                if($ladder == $height) {
                    $start = $ptr;
                    break;
                }
                $ladder++;

            }
            else if($char == "}" || $char == "]") {
                $ladder--;
            }

            $ptr--;
        }

        $ptr = $pos;

        while($ptr !== $length) {
            $char = \Str::substr($source, $ptr, 1);

            if($char == "{" || $char == "[") {
                $ladder--;
            }
            else if($char == "}" || $char == "]") {
                if($ladder == $height * 2) {
                    $end = $ptr + 1;
                    break;
                }
                $ladder++;
            }

            $ptr++;
        }

        $substring = \Str::substring($source, $start, $end);

        if($substring) {
            if(($compound = json_decode($substring, $assoc)) !== false) {
                return $compound;
            }
        }

        return null;
    }
}
