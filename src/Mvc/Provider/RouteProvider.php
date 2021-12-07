<?php

namespace Slate\Mvc\Provider {

    use Slate\Exception\HttpException;
    use Slate\Facade\Router;
    use Slate\Foundation\Provider;
    use Slate\Mvc\Result;
    use Slate\Mvc\Result\AnyResult;
    use Slate\Mvc\Result\CommandResult;
    use Slate\Mvc\Result\DataResult;

class RouteProvider extends Provider {
        public function boot(): void {
            $request = $this->app->request();
            $response = $this->app->response();

            $response->elapsed("init");
            
            /** Start a buffer to avoid premature response sending */
            // ob_start();


            if(($match = Router::match($request)) !== null ? $match[1] !== null : false) {
                list($routeInstance, $routeMatch) = $match;

                $controllerWebPath      = $routeMatch["webpath"];
                $controllerArguments    = $routeMatch["arguments"];

                $request->parameters = $controllerArguments;

                $routeResult = $routeInstance->go($request, $response, $routeMatch);

                if($routeResult !== null) {
                    if(!(is_object($routeResult) ? \Cls::isSubclassInstanceof($routeResult, Result::class) : false))
                        $routeResult = new AnyResult($routeResult);
                
                    if(\Cls::isSubclassInstanceof($routeResult, CommandResult::class)) {
                        $routeResult->modify($response);
                    }
                    else if(\Cls::isSubclassInstanceof($routeResult, DataResult::class)) {
                        $response->getBody()->write($routeResult->toString());
                        $response->headers["Content-Type"] = $routeResult->getMime();
                    }
                }

                $response->send();

                // ob_end_flush();
            }
            else {
                throw new HttpException(404, "No route by that path was found.");
            }
        }
    }
}

?>