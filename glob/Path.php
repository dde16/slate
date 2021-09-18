<?php

abstract class Path {
    const SEPARATOR =
        DIRECTORY_SEPARATOR;

    const PATTERN = "/^(?'directory'(?:\/?(?:[\w^ ]+)+)*)\/(?'basename'(?'filename'[\w]+)(?:\.(?'extension'[\w\.]*))?)$/";
    const PART_PATTERN = "[A-Za-z0-9\-\.\_\~\!\$\&\'\(\)\*\+\,\;\=\:\@\%]+";

    public static function hasExtension(string $path): bool {
        return (@\Path::info($path)["extension"]) !== null;
    }

    //TODO: remove
    public static function decompose(string $path): array|null {
        $matches = [];

        if(preg_match(\Path::PATTERN, $path, $matches)) {
            $extensions = \Str::split($matches["extension"], ".");
            $extension = \Arr::last($extensions);
            $filename = $matches["filename"];

            if(\Arr::count($extensions) > 1) {
                $filename .=  "." . \Arr::join(\Arr::slice($extensions, 0, -1), ".");
            }

            return [
                "directory" => $matches["directory"],
                "basename"  => $matches["basename"],
                "filename"  => $filename,
                "extension" => $extension
            ];
        }

        return null;
    }

    public static function getPermissions(string $path): int|false {
        if(!\Path::exists($path)) {
            throw new Slate\Exception\PathNotFoundException([
                "path" => $path
            ]);
        }

        return fileperms($path);
    }

    public static function getOwner(string $path): int|false {
        if(!\Path::exists($path)) {
            throw new Slate\Exception\PathNotFoundException([
                "path" => $path
            ]);
        }

        return fileowner($path);
    }

    public static function chmod(string $path, int $mode): bool {
        if(!\Path::exists($path)) {
            throw new Slate\Exception\PathNotFoundException([
                "path" => $path
            ]);
        }

        return chmod($path, $mode);
    }

    public static function chown(string $path, $user): bool {
        if(!\Path::exists($path)) {
            throw new Slate\Exception\PathNotFoundException([
                "path" => $path
            ]);
        }

        return chown($path, $user);
    }

    public static function copy(string $source, string $destination): bool {
        return copy($source, $destination);
    }

    public static function getFreeSpace(string $path): float|false {
        if(!\Path::exists($path)) {
            throw new Slate\Exception\PathNotFoundException([
                "path" => $path
            ]);
        }

        if(!\Path::isFile($path)) {
            throw new Exception(
                \Str::format(
                    "Path {path} must be a directory.",
                    $path
                )
            );
        }

        return disk_total_space($path);
    }

    public static function getTotalSpace(string $path): float|false {
        if(!\Path::exists($path)) {
            throw new Slate\Exception\PathNotFoundException([
                "path" => $path
            ]);
        }

        if(!\Path::isFile($path)) {
            throw new Exception(
                \Str::format(
                    "Path {path} must be a directory.",
                    $path
                )
            );
        }

        return disk_total_space($path);
    }

    public static function tree(string $path): array {
        $files = [];
        $path = \Path::resolve($path);

        if(\Path::isDir($path)) {
            foreach(\Path::scan($path) as $index => $object) {
                if(!\Str::isDotlink($object)) {
                    $location = $path . "/" . $object;

                    if(\Path::isFile($location)) {
                        $files[] = $location;
                    }
                    else {
                        $files = \Arr::merge($files, \Path::tree($location));
                    }
                }
            }
        }
        else {
            throw new Slate\Exception\PathNotFoundException([
                "path" => $path
            ]);
        }

        return $files;
    }

    public static function getFilename($path): string  {
        return basename($path);
    }

    public static function getFiles(string $path, bool $relative = true): array {
        $files = [];

        if(\Path::isDir($path)) {
            foreach(\Path::scan($path) as $index => $object) {
                if(!\Str::isDotlink($object)) {
                    $location = $path . "/" . $object;

                    if(\Path::isFile($location)) {
                        $files[] = ($relative) ? $object : $location;
                    }
                }
            }
        }
        else {
            throw new Slate\Exception\PathNotFoundException([
                "path" => $path
            ]);
        }

        return $files;
    }

    public static function info(string $path): array {
        return \Arr::key(pathinfo($path), ["dirname" => "directory"]);
    }

    public static function real(string $path): string|false {
        return realpath($path);
    }

    public static function scan(string $path): array|false {
        return scandir($path);
    }

    public static function relativeTo(string $source, string $destination): string {
        
        list($prefix, list($source, $destination)) = \Str::getPrefix([$source, $destination]);
        
        $traversal = \Arr::join([
            ...\Arr::map(
                \Arr::slice(\Str::split($source, "/"), 0, -1),
                function() {
                    return "..";
                }
            ),
            $destination
        ], "/");

        return (\Str::isEmpty($traversal))
            ? \Str::afterLast($prefix, "/") : $traversal;
    }

    /**
     * Used to resolve the path, with or without checking if the file exists or not.
     *
     * @param path The path we are wanting to check.
     * @param manual Whether to check if the path exists or not.
     * @return bool
     */
    public static function resolve(string $path, bool $manual = false): string|null {
        $result = null;

        if($manual) {
            $result = \Arr::reduce(
                \Str::split(
                    $path,
                    \Path::SEPARATOR
                ),
                function($a, $b) {
                    if($a === 0)
                        $a = \Path::SEPARATOR;

                    if($b === "" || $b === ".")
                        return $a;

                    if($b === "..")
                        return dirname($a);

                    return preg_replace(
                        "/\\".\Path::SEPARATOR."+/", \Path::SEPARATOR, "$a/$b");
                },
                0
            );
        }
        else {
            if($_result = \Path::real($path)) {
                if(!\Str::isEmpty($_result)) {
                    $result = $_result;
                }
            }
        }

        return $result;
    }

    /**
     * Used to check whether a path is safe.
     *
     * @param root The root path, acting as the boundaries of the check.
     * @param path The path we are wanting to check.
     * @param manual Whether to check if the path exists or not.
     * @return bool
     */
    public static function safe(string $root, string $path, bool $manual = false): string|null {
        $absolute = \Path::resolve($path, $manual);

        if($absolute !== NULL) {
            $rootLength = \Str::len($root);
            $pathLength = \Str::len($path);

            if($rootLength <= $pathLength) {
                if(\Str::substr($absolute, 0, $rootLength) === $root) {
                    return $absolute;
                }
            }
        }

        return null;
    }

    public static function normalise(string $path): string {
        return "/" . \Str::trimPrefix(
            \Str::trimSuffix(
                $path,
                "/"
            ),
            "/"
        );
    }

    public static function basename(string $path): string {
        return basename($path);
    }

    public static function exists(string $path): bool {
        return file_exists($path);
    }

    public static function isDir(string $path): bool {
        return \Path::exists($path) ? is_dir($path) : false;
    }

    public static function isDirectory(string $path): bool {
        return \Path::isDir($path);
    }


    public static function isFile(string $path): bool {
        return \Path::exists($path) ? is_file($path) : false;
    }

    public static function isReadable(string $path): bool {
        return \Path::exists($path) ? is_readable($path) : false;
    }

    public static function isWritable(string $path): bool {
        return \Path::exists($path) ? is_writable($path) : false;
    }
}
