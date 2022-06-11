<?php declare(strict_types = 1);

namespace Slate\Mvc {

    use Closure;
    use Slate\Exception\PregException;
    use Slate\Media\Uri;
    use Slate\Media\UriPath;

    class RouteUri extends Uri {
        public const PARAMETER_PATTERN      =
            "/(?'match'
                (?'param'
                    (?'param_leading_slash'\/)?
                    (?'param_body'
                        \{
                            (?'param_name'[^\}\/]+)
                        \}
                        (?'param_optional'\?)?
                    )
                    (?'param_trailing_slash'\/)?
                )
            )/x";

        public function __construct(string $uri = null) {
            if($uri !== null) {
                $parsed = static::parse($uri);

                $this->scheme   = $parsed["scheme"];
                $this->host     = $parsed["host"];
                $this->port     = $parsed["port"];
                $this->user     = $parsed["user"];
                $this->pass     = $parsed["pass"];

                $prefix = $this->toString();
                
                $this->setPath(\Path::normalise($prefix ? \Str::removePrefix($parsed["pathIncludingQuery"], $prefix) : $parsed["pathIncludingQuery"]));
                
                if(!empty($parsed["fragment"]))
                    throw new \Error("Rest URIs are not allowed fragments.");
            }
        }

        public array $params    = [];
        public ?string $prefix = null;

        public function setPath(string $path): void {
            parent::setPath($path);

            $matches = [];

            $this->params = [];

            if(preg_match_all(static::PARAMETER_PATTERN, $path, $matches, PREG_UNMATCHED_AS_NULL | PREG_OFFSET_CAPTURE)) {
                $matches = 
                    \Arr::map(
                        \Arr::decategorise($matches),
                        function($matches) {
                            return \Arr::map(
                                \Arr::filter($matches, fn($value, $key) => ($value[0] !== null ? !\Str::isEmpty($value[0]) : false) && !is_int($key), \Arr::FILTER_BOTH),
                                function($match) {
                                    $len = strlen($match[0]);

                                    return [...$match, $match[1] + $len]; 
                                }
                            );
                        }
                    )
                ;

                $matches = \Arr::map(
                    $matches,
                    function($lastMatches) use($matches) {

                        $lastMatches["match"] = [
                            ...$lastMatches["match"],
                            \Arr::first(
                                $matches,
                                fn($nextMatches) => $lastMatches["match"][1] === $nextMatches["match"][2]
                            ),
                            \Arr::first(
                                $matches,
                                fn($nextMatches) => $lastMatches["match"][2] === $nextMatches["match"][1]
                            )
                        ];

                        return $lastMatches;
                    }
                );

                foreach($matches as $match) {
                    if($match["param"] !== null) {
                        if(\Arr::hasKey($this->params, $match["param_name"][0]))
                            throw new \Error("Duplicate parameter '{$match['param_name'][0]}' detected in '{$path}'.");

                        
                        // throw new Error("Parameter {$match["param_name"][0]} cannot have another parameter directy after it.");

                        $this->params[$match["param_name"][0]] = [
                            "name"      => $match["param_name"][0],
                            "from"      => $match["param_body"][1],
                            "to"        => $match["param_body"][2],
                            "soft"      => $match["param_leading_slash"] !== null && $match["match"][4]["param_leading_slash"] !== null && $match["param_optional"] !== null,
                            "optional"  => $match["param_optional"] !== null,
                            "leading_slash"     => $match["param_leading_slash"] !== null,
                            "trailing_slash"     => $match["param_leading_slash"] !== null,
                            "ambiguous" => (
                                (
                                    !$match["param_leading_slash"]
                                    && !(
                                        $match["match"][3]
                                            ? $match["match"][3]["param_trailing_slash"]
                                            : true
                                    )
                                )
                                || (
                                    !$match["param_trailing_slash"]
                                    && !(
                                        $match["match"][4]
                                            ? $match["match"][4]["param_leading_slash"]
                                            : true
                                    )
                                )
                            )
                        ];
                    }
                }
            }
            else {
                PregException::last();
            }

            [$minParamName, $minParam] = \Arr::minEntry(
                $this->params,
                fn(array $param): int => $param["from"]
            );

            if($minParam !== null) {
                $this->prefix = \Str::removeSuffix(substr($this->toString(), 0, $minParam["from"]), "/");
            }
                

            // Perform paramter and wildcard match
            // When invoked; if a where regex is provided for a parameter, whether two ambiguous parameters are directly touching 

        }

        public function slashes(): int {
            $slashes = 
                \Str::count($this->getPath()->toString(), "/")
                - \Arr::count(\Arr::filter(
                    $this->params,
                    fn($param) => !(!$param["optional"] && $param["leading_slash"] )
                ));

            return $slashes;
        }

        public function pattern(array $wheres = []): string {
            return \Str::replaceManyAt(
                $this->getPath()->toString(),
                \Arr::mapAssoc(
                    $this->params,
                    function($paramName, $paramOptions) use($wheres) {
                        return [
                            $paramName,
                            [
                                "(?:"
                                    . ($paramOptions["optional"] || $paramOptions["leading_slash"] ? "/" : "")
                                    . "(?'param_$paramName'"
                                        . (@$wheres[$paramName] ?: "[^/]+")
                                    . ")"
                                . ")"
                                . (
                                    $paramOptions["optional"]
                                        ? "?"
                                        : ""
                                ),
                                $paramOptions["from"] - intval($paramOptions["optional"] || $paramOptions["leading_slash"]), $paramOptions["to"]
                            ]
                        ];
                    }
                )
            );
        }

        public function match(string|UriPath $uri, array $wheres = []): ?array {
            $requiredAbsentParams = \Arr::filter(
                $this->params,
                fn($param) => $param["ambiguous"] ? (@$wheres[$param["name"]] === null) : false
            );


            if(!\Arr::isEmpty($requiredAbsentParams))   
                throw new \Error(\Str::format(
                    "Parameters {} require where patterns as they are ambiguous.",
                    \Arr::list(
                        \Arr::map(
                            $requiredAbsentParams,
                            'name'
                        ),
                        ", ",
                        "``"
                    )
                ));


            // if($this->prefix !== null ? \Str::startswith($uri->toString(), $this->prefix) : true) {

            $pattern = $this->pattern($wheres);

            $match = [];

            if(preg_match("!^{$pattern}$!", $uri->toString(), $match, PREG_UNMATCHED_AS_NULL)) {
                $values = [];

                foreach($this->params as $param)
                    $values[$param["name"]] = ($value = $match["param_".$param["name"]]) !== null ? urldecode($value) : null;

                return $values;
            }
            // }

            return null;
        }

        public function withoutParams(): string {
            return \Str::replaceManyAt(
                $this->getPath(),
                \Arr::mapAssoc(
                    $this->params,
                    function($paramName, $paramOptions) {
                        return [
                            null,
                            [
                                "",
                                $paramOptions["from"] - intval($paramOptions["leading_slash"]), $paramOptions["to"]
                            ]
                        ];
                    }
                )
            );
        }

        public function restformat(array $params): string {
            return \Str::replaceManyAt(
                $this->getPath(),
                \Arr::mapAssoc(
                    $this->params,
                    function($paramName, $paramOptions) use($params) {
                        return [
                            null,
                            [
                                $params[$paramName] !== null ? $params[$paramName] : "",
                                $paramOptions["from"] - ($paramOptions["slashes"] ? 1 : 0), $paramOptions["to"]
                            ]
                        ];
                    }
                )
            );
        }
    }
}

?>