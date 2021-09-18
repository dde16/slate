<?php

class Base64 {
    use Slate\Utility\TUninstantiable;

    public static function tryparse(string $base64): string {
        if(($plaintext = base64_decode($base64, true)) === false)
            throw new Slate\Exception\ParseException("Error while parsing base64 string.");

        return $plaintext;
    }
}

?>