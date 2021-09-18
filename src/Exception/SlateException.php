<?php

namespace Slate\Exception {
    
    use Slate\Http\HttpCode;
    use Throwable;

abstract class SlateException extends \Exception {
        public function __construct($arguments) {
            $message = "";

            if(\Any::isString($arguments)) {
                $message = $arguments;
            }
            else {
                $message = \Str::format(
                    $this->format,
                    $arguments
                );
            }

            parent::__construct(
                $message,
                $this->code
            );
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