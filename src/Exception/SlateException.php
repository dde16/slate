<?php

namespace Slate\Exception {

    use Closure;
    use Slate\Http\HttpCode;
    use Throwable;
    use Exception;

    abstract class SlateException extends Exception {
        public const ERROR_DEFAULT          = (1<<0);

        /**
         * Stores predefined messages in the format
         */
        public const ERROR_MESSAGES = [];

        public function __construct(string|array $argument = null, int $code = 0, ?Throwable $previous = null) {
            $message = $argument;

            if($argument === null || is_array($argument))
                $message = \Str::format(static::ERROR_MESSAGES[$code], $argument ?: []);

            parent::__construct($message, $code, $previous); 
        }
        
        public static function httpify($throwable): Throwable {
            if(!\Cls::isSubclassInstanceof($throwable, HttpException::class)) {
                $throwable->{"httpCode"}       = 500;
                $throwable->{"httpMessage"}    = HttpCode::message(500);
            }

            return $throwable;
        }
        
        /**
         * Get the HTML output of a Throwable.
         *
         * @param  mixed $httpCode
         * @param  mixed $throwable
         * 
         * @return string
         */
        public static function getHtml(\Throwable $throwable, int $httpCode = 500): string {
            $tab = "&nbsp;&nbsp;&nbsp;&nbsp;";
            return \Str::val(\Cls::getClass($throwable)).": ".
                "<br>".$tab."Http Code    : ".\Str::wrap(\Str::val(\Compound::get($throwable, "httpCode", 500)), "'").
                "<br>".$tab."Http Message : ".\Str::wrap(HttpCode::message($httpCode), "'").
                "<br>".$tab."Dev  Message : ".\Str::wrap($throwable->getMessage(), "'").
                "<br>".$tab."File         : ".\Str::wrap($throwable->getFile(), "'").
                "<br>".$tab."Line         : ".\Str::wrap($throwable->getLine(), "'").
                "<br>".$tab."Traceback".
                "<br>".\Arr::join(\Arr::map(
                    \Str::replace(
                        \Str::split(
                            $throwable->getTraceAsString(),
                            "\n"
                        ),
                        " ",
                        "&nbsp;"
                    ),
                    function($v) use($tab) {
                        return $tab.$tab.$v."<br>";
                    }
                ), "\n");
        }

    }
}


?>