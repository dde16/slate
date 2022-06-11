<?php declare(strict_types = 1);

namespace Slate\Facade {

    use Closure;
    use Slate\Utility\Facade;

    final class Security extends Facade {
        public static function sanitise(mixed $input, array $escape = ["\"", "'", "`", "\\"], bool|string $html = false): mixed {
            $output = null;
    
            switch(\Any::getType($input)) {
                case "string":
                    $output = $input;

                    if($escape !== null ? !\Arr::isEmpty($escape) : false) {
                        $output = \Str::escape($input, $escape);
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