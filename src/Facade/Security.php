<?php

namespace Slate\Facade {

    use Closure;
    use Slate\Utility\Facade;

    final class Security extends Facade {
        public static function sanitise(mixed $input, array $escape = ["\"", "'", "`", "\\"], bool|string $html = false): mixed {
            $output = null;
    
            switch(\Any::getType($input)) {
                case "string":
                    $output = $input;

                    if($escape !== null) {
                        $output = preg_replace_callback(
                            "/(\\\\)*(" . \Arr::join(\Arr::map($escape, Closure::fromCallable('preg_quote')), "|") . ")/",
                            function($matches) use($escape) {
                                $match = $matches[2];
                                $escapes = $matches[1];

                                if(empty($escapes))
                                    $escapes = "\\";

                                if(strlen($escapes) % 2 === 0)
                                    $escapes .= "\\";
    
                                return $escapes.$match;
                            },
                            $input
                        );
                    }
    
                    switch($html) {
                        case true:
                        case "all":
                            $output = htmlentities($output);
                            break;
                        case "specials":
                            $output = htmlspecialchars($output);
                            break;
                    }
                    break;
                case "object":
                case "array":
                    $output = $input;
    
                    foreach($output as $key => $value)
                        \Compound::set($output, $key, Security::sanitise($value, $escape));

                    break;
                default:
                    $output = $input;
                    break;
            }
    
            return $output;
        }
    }
}

?>