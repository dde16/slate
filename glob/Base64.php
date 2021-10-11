<?php

class Base64 {
    use Slate\Utility\TUninstantiable;

    /**
     * Try and decode/parse a base64 string and raise on error.
     * 
     * @param string $base64
     * 
     * @return string
     */
    public static function tryparse(string $base64): string {
        if(($plaintext = base64_decode($base64, true)) === false)
            throw new Slate\Exception\ParseException("Unknown error while parsing base64 string.");

        return $plaintext;
    }
}

?>