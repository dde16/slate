<?php

namespace Slate\Foundation\Provider {

    use Slate\Exception\SlateException;
    use Slate\Foundation\Provider;
    use Slate\Mvc\Env;
    use Throwable;

    class HandlerProvider extends Provider {
        public function register(): void {
            $dnt = false;

            set_exception_handler(function(Throwable $throwable) use($dnt) {
                $this->lastError = $throwable;

                try {
                    $response = $this->app->response();

                    /** Whether the error page has been resolved or not */
                    $errorPageResolve = false;
                    
                    /** Set response code to one of HttpException or 500 internal server error */
                    $httpCode =
                        \Cls::isSubclassInstanceOf($throwable, HttpException::class)
                            ? ($throwable->httpCode)
                            : 500;

                    $response->status = $httpCode;

                    $mvcRootDirectory = Env::get("mvc.path.absolute.root");
                    $mvcViewsDirectory = Env::get("mvc.path.absolute.views");
                    $mvcErrorPage = Env::get("mvc.security.errorPage");

                    /** Check if all of the paths are not empty */
                    if($mvcViewsDirectory !== null && $mvcViewsDirectory !== null && $mvcErrorPage !== null) {
                        /** Normalise the error page path */
                        $mvcErrorPage = \Path::normalise($mvcErrorPage);

                        /** Check if the views directory exists */
                        if(\Path::exists($mvcViewsDirectory)) {
                            /** Error page must be in the views directory */
                            $mvcErrorPage = $mvcViewsDirectory.$mvcErrorPage;

                            /** Check if the page exists and is safe */
                            if($mvcErrorPage = \Path::safe($mvcRootDirectory, $mvcErrorPage)) {
                                /** Set the data of the exception */
                                $_DATA = [
                                    "custom" => [
                                        "Throwable" => $throwable
                                    ]
                                ];

                                /** Get page output */
                                ob_start();
                                include($mvcErrorPage);
                                $body = ob_get_contents();
                                ob_end_clean();

                                $errorPageResolve = true;

                                $response->body = $body;
                            }
                        }
                    }

                    /** If there is no error page */
                    if(!$errorPageResolve) {
                        $verbosePageAllowed = false;

                        /** Check if the verbose flag is set */
                        if(($mvcVerbose = Env::get("mvc.verbose"))  !== null) {
                            $verbosePageAllowed = \Boolean::tryparse($mvcVerbose);
                        }

                        /** If verbose then display output */
                        if($verbosePageAllowed) {
                            // ob_clean();
                            $response->headers["Content-Type"] = "text/html";
                            echo (SlateException::getHtml($throwable, $httpCode));
                        }
                        else {
                            $dnt = true;
                            throw $throwable;
                        }
                    }
                }
                catch(\Throwable $throwable) {
                    if($dnt === false) {
                        echo "An error occured, while handling another error; verbosity will be ignored.";
                        echo SlateException::getHtml($throwable);
                    }
                    else {
                        throw $throwable;
                    }
                }
                finally {
                    $response->send();
                }
            });
        }
    }
}

?>