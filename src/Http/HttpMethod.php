<?php

namespace Slate\Http {
    use Slate\Structure\Enum;

    class HttpMethod extends HttpSupportedMethod {
        const PATCH   = (1<<4);
        const DELETE  = (1<<5);
        const CONNECT = (1<<6);
        const OPTIONS = (1<<7);
        const TRACE   = (1<<8);


        const ALL =
            HttpMethod::SUPPORTED |
            HttpMethod::PATCH |
            HttpMethod::DELETE |
            HttpMethod::CONNECT |
            HttpMethod::OPTIONS |
            HttpMethod::TRACE;
    }
}

?>