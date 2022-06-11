<?php declare(strict_types = 1);

namespace Slate\Http {
    use Slate\Structure\Enum;

    class HttpSupportedMethod extends Enum {
        const GET     = (1<<0);
        const POST    = (1<<1);
        const PUT     = (1<<2);
        const HEAD    = (1<<3);

        const SUPPORTED =
            HttpSupportedMethod::GET |
            HttpSupportedMethod::POST |
            HttpSupportedMethod::PUT |
            HttpSupportedMethod::HEAD;
    }
}

?>