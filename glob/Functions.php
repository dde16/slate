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

// function t(mixed $cond, mixed $val): mixed {
//     return $cond ? $val : null;
// }

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

/**
 * Create a FileResult to get the contents of a file and output it.
 *
 * @param string $path
 * @param string|null $mime
 *
 * @return Slate\Mvc\Result\FileResult
 */
function contents(string $path, ?string $mime = null): Slate\Mvc\Result\FileResult {
    return(new Slate\Mvc\Result\FileResult($path, $mime));
}

/**
 * Create a view result.
 *
 * @param string $path
 * @param array $data
 *
 * @return Slate\Mvc\Result\ViewResult
 */
function view(string $path, array $data = []): Slate\Mvc\Result\ViewResult {
    return(new Slate\Mvc\Result\ViewResult($path, $data));
}

/**
 * Create a data result.
 *
 * @param string $data
 * @param string $mime
 * @param boolean $bypass
 *
 * @return Slate\Mvc\Result\ScalarResult
 */
function data(string $data, string $mime = "text/html", bool $bypass = false): Slate\Mvc\Result\ScalarResult {
    return(new Slate\Mvc\Result\ScalarResult($data, mime: $mime, bypass: $bypass));
}

/**
 * Create a redirect result.
 *
 * @param string $path
 * @param string $mode
 *
 * @return Slate\Mvc\Result\RedirectResult
 */
function redirect(string $path, string $mode = "temporary"): Slate\Mvc\Result\RedirectResult {
    return(new Slate\Mvc\Result\RedirectResult($path, $mode));
}

/**
 * Get a route string by its name.
 *
 * @param string $name
 * @param array $data
 *
 * @return string|null
 */
function route(string $name, array $data = []): string|null {
    foreach(\Arr::flatten(Slate\Facade\Router::routes()) as $route)
        if($route->name === $name)
            return $route->format($data);

    return null;
}

/**
 * Create a status code result.
 *
 * @param integer $code
 *
 * @return Slate\Mvc\Result\StatusCodeResult
 */
function code(int $code): Slate\Mvc\Result\StatusCodeResult {
    return(new Slate\Mvc\Result\StatusCodeResult($code));
}

function debug($value = "", $anonymous = null): void {
    $options = null;
    $newline = "\r\n";

    if($anonymous !== NULL) {
        if(is_array($anonymous)) {
            $options = $anonymous;

            if(\Arr::hasKey($options, "nl")) {
                $newline = $options["nl"];
            }
        }
        else if(is_string($anonymous)) {
            $newline = $anonymous;
        }
    }

    $value = \Str::repr($value).$newline;

    if($options !== NULL) {
        $value = \Str::sanitise($value, $options);
    }

    print($value);
}

/**
 * Create a collection.
 *
 * @param \Slate\Data\Collection|null|array $source
 *
 * @return Slate\Data\Collection
 */
function collect(\Slate\Data\Collection|null|array $source = null): Slate\Data\Collection {
    return !($source instanceof Slate\Data\Collection) ? (new Slate\Data\Collection($source ?? [])) : $source;
}

/**
 * Check a value is exclusively not null.
 *
 * @param mixed $v
 *
 * @return boolean
 */
function is_set($v): bool {
    return $v !== null;
}

/**
 * Recursively remove a directory.
 * 
 * TODO: convert to OOP
 *
 * @param string $basepath
 * @param boolean $symlinks
 *
 * @return boolean
 */
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

/**
 * Quickly get an environment variable.
 *
 * @param string $offset
 * @param array $options
 *
 * @return mixed
 */
function env(string $offset, array $options = []): mixed {
    return Slate\Mvc\Env::get($offset, $options);
}

/**
 * Get a SqlSchema.
 *
 * @param string $name
 *
 * @return Slate\Sql\SqlSchema
 */
function Schema(string $name): Slate\Sql\SqlSchema {
    return new Slate\Sql\SqlSchema(Slate\Facade\App::conn(), $name);
}

/**
 * Convert a list of path segments to a path.
 *
 * @param string ...$parts
 *
 * @return string
 */
function path(string ...$segments): string {
    return "/".\Arr::join(
        \Arr::map($segments, fn(string $segment): string => \Str::removeAffix($segment, "/")),
        "/"
    );
}

/**
 * Get the http request for the current lifecycle.
 * 
 * @return \Slate\Http\HttpRequest
 */
function request(): \Slate\Http\HttpRequest {
    return \Slate\Facade\App::request();
}

?>