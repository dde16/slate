<?php

namespace Slate\Http {
    abstract class HttpEnvironment {
        public static function getBrowser(string $userAgent): array|null { 
            if(($browser = get_browser($userAgent, true)) !== false) {
                \Arr::modify($browser, ["ismobiledevice", "istablet", "crawler"], function($value) {
                    return !\Str::isEmpty($value) ? (\Boolean::parse($value) ?: false) : false;
                });
            }
            else {
                $browser = null;
            }
            

            return $browser;
        }

        public static function isLinux(): bool {
            return \Str::upper(\Str::substr(PHP_OS, 0, 3));
        }

        public static function getRequestTimeFloat(): float {
            return $_SERVER["REQUEST_TIME_FLOAT"];
        }

        public static function hasExtensions(array $names): bool {
            return \Arr::all($names, 'extension_loaded');
        }

        public static function hasExtension(string $name): bool {
            return extension_loaded($name);
        }

        public static function getElapsedTime(int $precision = 4): int {
            return round(
                microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                $precision
            ) * 1000;
        }

        public static function getProtocol(): string {
            return $_SERVER["REQUEST_SCHEME"];
        }

        public static function getVersion(): float {
            return (float)\Str::split($_SERVER["SERVER_PROTOCOL"], "/")[1];
        }

        public static function getMethod(): string {
            return $_SERVER["REQUEST_METHOD"];
        }

        public static function getCookies(): array {
            return $_COOKIE;
        }

        public static function getPath(): string {
            return \Path::normalise(
                \Str::beforeFirst($_SERVER["REQUEST_URI"], "?")
            );
        }

        public static function getHost(): string|null {
            return $_SERVER["SERVER_NAME"];
        }

        public static function getQuery(): array|null {
            $method = self::getMethod();

            switch($method) {
                default:
                case "GET":
                    return $_GET;
                    break;
                case "POST":
                    return $_POST;
                    break;
            }

            return null;
        }

        public static function getHeaders(): array {
            $headers = [];

            $map = array(
                "CONTENT_TYPE",
                "CONTENT_LENGTH",
                "CONTENT_MD5",
            );

            $prefix = "HTTP_";

            foreach($_SERVER as $key => $value) {
                if(\Str::startswith($key, $prefix)) {
                    $key = \Str::removePrefix($key, $prefix);
                }
                else if(!\Arr::contains($map, $key)) {
                    $key = null;
                }

                if($key !== NULL) {
                    $key = \Str::replace(
                        ucwords(
                            \Str::lowercase(
                                \Str::replace(
                                    $key,
                                    "_",
                                    " "
                                )
                            )
                        ),
                        " ",
                        "-"
                    );

                    $headers[$key] = $value;
                }
            }

            if (!isset($headers["Authorization"])) {
                if(isset($_SERVER["REDIRECT_HTTP_AUTHORIZATION"])) {
                    $headers["Authorization"] = $_SERVER["REDIRECT_HTTP_AUTHORIZATION"];
                }
                else if (isset($_SERVER["PHP_AUTH_USER"])) {
                    $basicPassword = isset($_SERVER["PHP_AUTH_PW"]) ? $_SERVER["PHP_AUTH_PW"] : "";
                    $headers["Authorization"] = "Basic " . base64_encode($_SERVER["PHP_AUTH_USER"] . ":" . $basicPassword);
                }
                else if (isset($_SERVER["PHP_AUTH_DIGEST"])) {
                    $headers["Authorization"] = $_SERVER["PHP_AUTH_DIGEST"];
                }
            }

            return $headers;
        }

        public static function getBody(): string {
            return file_get_contents("php://input");
        }

        public static function getFiles(): array {
            return \Arr::mapAssoc(
                $_FILES,
                function($field, $file) {
                    return [
                        $field,
                        (new HttpRequestFile(
                            $field,
                            $file["name"],
                            $file["tmp_name"],
                            $file["type"],
                            $file["error"],
                            $file["size"],
                        ))
                    ];
                }
            );
        }

        public static function getClientInfo(): array {
            $ip = self::getClientIpAddress();
            $port = self::getClientPort();

            return [$ip, $port];
        }

        public static function getClientPort(): int {
            return $_SERVER["REMOTE_PORT"];
        }

        public static function getClientIpAddress(): string {
            // Get real visitor IP behind CloudFlare network
            if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
                $_SERVER["REMOTE_ADDR"] = $_SERVER["HTTP_CF_CONNECTING_IP"];
                $_SERVER["HTTP_CLIENT_IP"] = $_SERVER["HTTP_CF_CONNECTING_IP"];
            }
            $client  = @$_SERVER["HTTP_CLIENT_IP"];
            $forward = @$_SERVER["HTTP_X_FORWARDED_FOR"];
            $remote  = $_SERVER["REMOTE_ADDR"];

            if(\Str::isIpAddress($client)) {
                $ip = $client;
            }
            else if(\Str::isIpAddress($forward)) {
                $ip = $forward;
            }
            else {
                $ip = $remote;
            }

            return $ip;
        }
    }
}
