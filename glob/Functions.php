<?php

/**
 * Calculates the time elapsed for a given function.
 *
 * @param  callable $callback
 * @param  int      $precision Precision of the miliseconds
 * 
 * @return array
 */
function elapsed(callable $callback, int $precision = 4): array {
    $start = microtime(true);
    
    $return = $callback();

    $end = microtime(true);

    return [$return, round($end - $start, $precision) * 1000];
}

function callerof(string $function = null, array $use = null): array|null {
    $stack = $use === null ? debug_backtrace() : $use;

    if ($function == null) {
        // We need $function to be a function name to retrieve its caller. If it is omitted, then
        // we need to first find what function called get_caller(), and substitute that as the
        // default $function. Remember that invoking get_caller() recursively will add another
        // instance of it to the function stack, so tell get_caller() to use the current stack.
        $function = callerof(__FUNCTION__, $stack)["function"];
    }

    if($function !== null){
        // If we are given a function name as a string, go through the function stack and find
        // it's caller.
        for ($i = 0; $i < count($stack); $i++) {
            $currentFunction = $stack[$i];
            // Make sure that a caller exists, a function being called within the main script
            // won't have a caller.
            if ($currentFunction["function"] === $function&& ($i + 1) < count($stack)) {
                return $stack[$i + 1];
            }
        }
    }

    // At this stage, no caller has been found, bummer.
    return null;
}

function contents(string $path, ?string $mime = null): Slate\Mvc\Result\FileResult {
    return(new Slate\Mvc\Result\FileResult($path, $mime));
}

function view(string $path, array $data = []): Slate\Mvc\Result\ViewResult {
    return(new Slate\Mvc\Result\ViewResult($path, $data));
}

function data(string $data, string $mime = "text/html", bool $bypass = false): Slate\Mvc\Result\ScalarResult {
    return(new Slate\Mvc\Result\ScalarResult($data, mime: $mime, bypass: $bypass));
}

function redirect(string $path, string $mode = "temporary"): Slate\Mvc\Result\RedirectResult {
    return(new Slate\Mvc\Result\RedirectResult($path, $mode));
}

function route(string $name, array $data = []): string|null {
    foreach(Slate\Mvc\Router::$routes as $slashes => $routes) {
        foreach($routes as $route) {
            if($route->name === $name) {
                return $route->format($data);
            }
        }
    }

    return null;
}

function code(int $code): Slate\Mvc\Result\StatusCodeResult {
    return(new Slate\Mvc\Result\StatusCodeResult($code));
}

function debug($value = "", $anonymous = null): void {
    $options = null;
    $newline = "\r\n";

    if($anonymous !== NULL) {
        if(\Any::isArray($anonymous)) {
            $options = $anonymous;

            if(\Arr::hasKey($options, "nl")) {
                $newline = $options["nl"];
            }
        }
        else if(\Any::isString($anonymous)) {
            $newline = $anonymous;
        }
    }

    $value = \Str::repr($value).$newline;

    if($options !== NULL) {
        $value = \Str::sanitise($value, $options);
    }

    print($value);
}

function collect($source = null): Slate\Data\Collection {
    if($source === NULL) {
        $source = [];
    }
    else if ($source instanceof Slate\Data\Collection) {
        return $source;
    }

    return (new Slate\Data\Collection($source));
}

function is_set($v): bool {
    return $v !== null;
}

function rrmdir(string $basepath, bool $symlinks = false): bool {
    $directory = opendir($basepath);

    while(($file = readdir($directory)) !== false) {
        if (!\Str::isDotLink($file)) {
            $fullpath = $basepath."/".$file;
            $isSymlink  = is_link($fullpath);

            if($isSymlink) $fullpath = readlink($fullpath);

            if(!$isSymlink || $symlinks) {
                if(is_dir($fullpath)) {
                    rrmdir($fullpath);
                }
                else {
                    unlink($fullpath);
                }
            }
        }
    }
    closedir($directory);
    
    return rmdir($basepath);
}

function env(string $offset, array $options = []): mixed {
    return Slate\Mvc\Env::get($offset, $options);
}

?>