<?php declare(strict_types = 1);

namespace Slate\Http {
    use Slate\Structure\Enum;

    class HttpProtocol extends Enum {
        const HTTP = 1;
        const HTTPS = 2;
    }
}
