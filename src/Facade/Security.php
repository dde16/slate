<?php

namespace Slate\Facade {

    use Closure;
    use Slate\Utility\Facade;

    final class Security extends Facade {

        public static function sanitise(mixed $input, array $escape = ["\"", "'", "`"]): mixed {
            $output = null;
    
            switch(\Any::getType($input)) {
                case "string":
                    $output = $input;
    
                    if($escape) {
                        $output = preg_replace_callback(
                            "/(" . implode("|", $escape) . ")/",
                            function($matches) use($escape) {
                                $match = $matches[0];
    
                                return "\\".$match;
                            },
                            $input
                        );
                    }
    
                    // if($html !== NULL) {
                    //     if(\Any::isInt($html)) {
                    //         switch($html) {
                    //             case "all":
                    //                 $output = htmlentities($output);
                    //                 break;
                    //             case "specials":
                    //                 $output = htmlspecialchars($output);
                    //                 break;
                    //         }
                    //     }
                    //     else if(\Any::isString($html)) {
                    //         $output = htmlentities($output);
                    //     }
                    // }
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