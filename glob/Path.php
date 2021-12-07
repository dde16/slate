<?php

class Path {
    const SEPARATOR =
        DIRECTORY_SEPARATOR;

    const PATTERN = "/^(?'directory'(?:\/?(?:[\w^ ]+)+)*)\/(?'basename'(?'filename'[\w]+)(?:\.(?'extension'[\w\.]*))?)$/";
    const PART_PATTERN = "[A-Za-z0-9\-\.\_\~\!\$\&\'\(\)\*\+\,\;\=\:\@\%]+";

    public static function assertFileExists(string $path): void {
        \Path::assertExists($path);

        if(is_file($path))
            throw new Slate\Exception\IOException([$path], Slate\Exception\IOException::ERROR_FILE_IS_DIR_MISMATCH);
    }

    public static function touch(string $path): void {
        $resource = fopen($path, "w");

        if($resource === FALSE) {
            throw new Slate\Exception\IOException(
                "Unable to open to touch file at '{$path}'.",
                Slate\Exception\IOException::ERROR_FILE_OPEN_FAILURE
            );
        }

        fclose($resource);
    }

    public static function assertDirExists(string $path): void {
        \Path::assertExists($path);

        if(is_file($path))
            throw new Slate\Exception\IOException([$path], Slate\Exception\IOException::ERROR_DIR_IS_FILE_MISMATCH);
    }

    public static function assertExists(string $path): void {
        if(!\Path::exists($path))
            throw new Slate\Exception\IOException([$path], Slate\Exception\IOException::ERROR_PATH_NOT_FOUND);
    }

    public static function hasExtension(string $path): bool {
        return (@\Path::info($path)["extension"]) !== null;
    }

    public static function getPermissions(string $path): int|false {
        \Path::assertExists($path);

        return fileperms($path);
    }

    public static function getOwner(string $path): int|false {
        \Path::assertExists($path);

        return fileowner($path);
    }

    public static function chmod(string $path, int $mode): bool {
        \Path::assertExists($path);

        return chmod($path, $mode);
    }

    public static function chown(string $path, $user): bool {
        \Path::assertExists($path);

        return chown($path, $user);
    }

    public static function getFreeSpace(string $path): float|false {
        \Path::assertDirExists($path);

        return disk_total_space($path);
    }

    public static function getTotalSpace(string $path): float|false {
        \Path::assertDirExists($path);
        
        return disk_total_space($path);
    }

    public static function tree(string $path): array {
        \Path::assertDirExists($path);
        
        $files = [];

        foreach(scandir($path) as $object) {
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

        return $files;
    }

    public static function info(string $path): array {
        return \Arr::key(pathinfo($path), ["dirname" => "directory"]);
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
            if($_result = realpath($path)) {
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

    public static function exists(string $path): bool {
        return file_exists($path);
    }

    public static function isDir(string $path): bool {
        return is_dir($path);
    }

    public static function isDirectory(string $path): bool {
        return \Path::isDir($path);
    }


    public static function isFile(string $path): bool {
        return is_file($path);
    }

    public static function isReadable(string $path): bool {
        return is_readable($path);
    }

    public static function isWritable(string $path): bool {
        return is_writable($path);
    }
}
