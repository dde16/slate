<?php

namespace Slate\Mvc {

    use Closure;
    use Slate\Media\Uri;

    class RouteUri extends Uri {
        public const PARAMETER_PATTERN      =
            "/(?'match'
                (?'param'
                    (?'param_leading_slash'\/)?
                    (?'param_body'
                        \{
                            (?'param_name'[^\}\/]+)
                        \}
                        (?'param_optional'\?|)
                    )
                    (?'param_trailing_slash'\/)?
                )
            )/xU";

        public function __construct(string $uri = null) {
            if($uri !== null) {
                $parsed = parse_url($uri);

                $this->scheme   = $parsed["scheme"];
                $this->host     = $parsed["host"];
                $this->port     = $parsed["port"];
                $this->user     = $parsed["user"];
                $this->pass     = $parsed["pass"];
                
                $this->setPath(\Path::normalise(\Str::removePrefix($uri, $this->toString())));
                
                if(!empty($parsed["fragment"]))
                    throw new \Error("Rest URIs are not allowed fragments.");
            }
        }

        public array $params    = [];

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
                            throw new \Error("Duplicate parameter '{$match['param_name'][0]}' detected.");

                        
                        // throw new Error("Parameter {$match["param_name"][0]} cannot have another parameter directy after it.");

                        $this->params[$match["param_name"][0]] = [
                            "name"      => $match["param_name"][0],
                            "from"      => $match["param_body"][1],
                            "to"        => $match["param_body"][2],
                            "soft"      => $match["param_leading_slash"] !== null && $match["match"][4]["param_leading_slash"] !== null && $match["param_optional"] !== null,
                            "optional"  => $match["param_optional"] !== null,
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

            // Perform paramter and wildcard match
            // When invoked; if a where regex is provided for a parameter, whether two ambiguous parameters are directly touching 

        }

        public function slashes(): int {
            return
                \Str::count($this->getPath(), "/")
                - \Arr::count(\Arr::filter(
                    $this->params,
                    fn($param) => $param["soft"] 
                ));
        }

        public function match(string|Uri $uri, array $wheres = []): ?array {
            if(is_string($uri))
                $uri = new Uri($uri);

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


            $pattern = \Str::replaceManyAt(
                $this->getPath(),
                \Arr::mapAssoc(
                    $this->params,
                    function($paramName, $paramOptions) use($wheres) {
                        return [
                            null,
                            [
                                "(?:"
                                    . ($paramOptions["soft"] ? "\/" : "")
                                    . "(?'param_$paramName'"
                                        . (@$wheres[$paramName] ?: "[^\/]+")
                                    . ")"
                                . ")"
                                . (
                                    $paramOptions["optional"]
                                        ? "?"
                                        : ""
                                ),
                                $paramOptions["from"] - ($paramOptions["soft"] ? 1 : 0), $paramOptions["to"]
                            ]
                        ];
                    }
                )
            );

            $match = [];

            if(preg_match("!^{$pattern}$!", $uri->getPath(), $match, PREG_UNMATCHED_AS_NULL)) {
                $values = [];

                foreach($this->params as $param) {
                    $values[$param["name"]] = urldecode($match["param_".$param["name"]]);
                }

                return $values;
            }

            return null;
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