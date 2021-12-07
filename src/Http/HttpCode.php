<?php

namespace Slate\Http {
    use Slate\Structure\Enum;

    class HttpCode extends Enum {
        // [Informational 1xx]
        const CONTINUE                        = 100;
        const SWITCHING_PROTOCOLS             = 101;
        // [Successful 2xx]
        const OK                              = 200;
        const CREATED                         = 201;
        const ACCEPTED                        = 202;
        const NONAUTHORITATIVE_INFORMATION    = 203;
        const NO_CONTENT                      = 204;
        const RESET_CONTENT                   = 205;
        const PARTIAL_CONTENT                 = 206;
        // [Redirection 3xx]
        const MULTIPLE_CHOICES                = 300;
        const MOVED_PERMANENTLY               = 301;
        const FOUND                           = 302;
        const SEE_OTHER                       = 303;
        const NOT_MODIFIED                    = 304;
        const USE_PROXY                       = 305;
        const UNUSED                          = 306;
        const TEMPORARY_REDIRECT              = 307;
        // [Client Error 4xx]
        const BAD_REQUEST                     = 400;
        const UNAUTHORIZED                    = 401; // Not authenticated
        const PAYMENT_REQUIRED                = 402;
        const FORBIDDEN                       = 403; // Not authorised to perform this action
        const NOT_FOUND                       = 404;
        const METHOD_NOT_ALLOWED              = 405;
        const NOT_ACCEPTABLE                  = 406;
        const PROXY_AUTHENTICATION_REQUIRED   = 407;
        const REQUEST_TIMEOUT                 = 408;
        const CONFLICT                        = 409;
        const GONE                            = 410;
        const LENGTH_REQUIRED                 = 411;
        const PRECONDITION_FAILED             = 412;
        const REQUEST_ENTITY_TOO_LARGE        = 413;
        const REQUEST_URI_TOO_LONG            = 414;
        const UNSUPPORTED_MEDIA_TYPE          = 415;
        const REQUESTED_RANGE_NOT_SATISFIABLE = 416;
        const EXPECTATION_FAILED              = 417;
        const UNPROCESSABLE_ENTITY            = 422;
        const TOO_MANY_REQUESTS               = 429;
        // [Server Error 5xx]
        const INTERNAL_SERVER_ERROR           = 500;
        const NOT_IMPLEMENTED                 = 501;
        const BAD_GATEWAY                     = 502;
        const SERVICE_UNAVAILABLE             = 503;
        const GATEWAY_TIMEOUT                 = 504;
        const VERSION_NOT_SUPPORTED           = 505;

        private static $messages = [
            // [Informational 1xx]
            HttpCode::CONTINUE                        => "Continue",
            HttpCode::SWITCHING_PROTOCOLS             => "Switching Protocols",
            // [Successful 2xx]
            HttpCode::OK                              => "OK",
            HttpCode::CREATED                         => "Created",
            HttpCode::ACCEPTED                        => "Accepted",
            HttpCode::NONAUTHORITATIVE_INFORMATION    => "Non-Authoritative Information",
            HttpCode::NO_CONTENT                      => "No Content",
            HttpCode::RESET_CONTENT                   => "Reset Content",
            HttpCode::PARTIAL_CONTENT                 => "Partial Content",
            // [Redirection 3xx]
            HttpCode::MULTIPLE_CHOICES                => "Multiple Choices",
            HttpCode::MOVED_PERMANENTLY               => "Moved Permanently",
            HttpCode::FOUND                           => "Found",
            HttpCode::SEE_OTHER                       => "See Other",
            HttpCode::NOT_MODIFIED                    => "Not Modified",
            HttpCode::USE_PROXY                       => "Use Proxy",
            HttpCode::UNUSED                          => "(Unused)",
            HttpCode::TEMPORARY_REDIRECT              => "Temporary Redirect",
            // [Client Error 4xx]
            HttpCode::BAD_REQUEST                     => "Bad Request",
            HttpCode::UNAUTHORIZED                    => "Unauthorized",
            HttpCode::PAYMENT_REQUIRED                => "Payment Required",
            HttpCode::FORBIDDEN                       => "Forbidden",
            HttpCode::NOT_FOUND                       => "Not Found",
            HttpCode::METHOD_NOT_ALLOWED              => "Method Not Allowed",
            HttpCode::NOT_ACCEPTABLE                  => "Not Acceptable",
            HttpCode::PROXY_AUTHENTICATION_REQUIRED   => "Proxy Authentication Required",
            HttpCode::REQUEST_TIMEOUT                 => "Request Timeout",
            HttpCode::CONFLICT                        => "Conflict",
            HttpCode::GONE                            => "Gone",
            HttpCode::LENGTH_REQUIRED                 => "Length Required",
            HttpCode::PRECONDITION_FAILED             => "Precondition Failed",
            HttpCode::REQUEST_ENTITY_TOO_LARGE        => "Request Entity Too Large",
            HttpCode::REQUEST_URI_TOO_LONG            => "Request-URI Too Long",
            HttpCode::UNSUPPORTED_MEDIA_TYPE          => "Unsupported Media Type",
            HttpCode::REQUESTED_RANGE_NOT_SATISFIABLE => "Requested Range Not Satisfiable",
            HttpCode::EXPECTATION_FAILED              => "Expectation Failed",
            HttpCode::UNPROCESSABLE_ENTITY            => "Unprocessable Entity",
            HttpCode::TOO_MANY_REQUESTS               => "Too Many Requests",
            // [Server Error 5xx]
            HttpCode::INTERNAL_SERVER_ERROR           => "Internal Server Error",
            HttpCode::NOT_IMPLEMENTED                 => "Not Implemented",
            HttpCode::BAD_GATEWAY                     => "Bad Gateway",
            HttpCode::SERVICE_UNAVAILABLE             => "Service Unavailable",
            HttpCode::GATEWAY_TIMEOUT                 => "Gateway Timeout",
            HttpCode::VERSION_NOT_SUPPORTED           => "HTTP Version Not Supported"
        ];

        public static function message($code): string|null {
            return @self::$messages[$code];
        }

        public static function isRedirect(int $code): bool {
            return $code >= HttpCode::MULTIPLE_CHOICES && $code <= HttpCode::TEMPORARY_REDIRECT;
        }

        public static function isError(int $code): bool {
            return $code >= self::BAD_REQUEST;
        }

        public static function haveBody($code): bool {
            return
                // True if not in 100s
                ($code < self::CONTINUE || $code >= self::OK) && // and not 204 NO CONTENT
                $code != self::NO_CONTENT && // and not 304 NOT MODIFIED
                $code != self::NOT_MODIFIED;
        }
    }
}

?>