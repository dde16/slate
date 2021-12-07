<?php

namespace Slate\IO {

    use Generator;
    use Slate\Exception\IOException;

    use Slate\Data\Iterator\IExtendedIterator;

    /**
     * An OOP style directory handler.
     */
    class Directory {
        protected string $path;
        protected $resource;

        public function __construct(string $path) {
            $this->path = \Path::normalise($path);
        }

        public function getPath(): string {
            return $this->path;
        }

        /**
         * @see mkdir
         */
        public function create(int $permissions = 0700, bool $recursive = false): bool {
            return mkdir($this->path, $permissions, $recursive);
        }
        
        /**
         * Open a file within the current directory.
         *
         * @param  mixed $path Must be relative to directory.
         * @param  mixed $mode
         * @return void
         */
        public function openFile(string $path, ?string $mode = null): File {
            $path = $this->path . \Path::normalise($path);

            $dir =  dirname($path);

            // If the file's directory doesn't exist.
            if(!\Path::isDir($dir) && !\Path::exists($path))
                mkdir($dir, \Path::getPermissions($this->path), true);

            return (new File($path, $mode));
        }

        /**
         * Create a child direction within the parent directory.
         *
         * @param  mixed $path Must be relative to directory.
         * @param  mixed $mode
         * @return void
         */
        public function createDirectory(string $path, int $permissions = null, bool $recursive = true): void {
            $fullpath = $this->path . \Path::normalise($path);

            if(!mkdir($fullpath, $permissions ?? \Path::getPermissions($this->path), recursive: $recursive)) 
                throw new IOException(["path" => $fullpath], IOException::ERROR_DIR_OPEN_FAILURE);
        }

        /**
         * Create a file within the current directory.
         *
         * @param  mixed $path Must be relative to directory.
         * @param  mixed $mode
         * @return void
         */
        public function createFile(string $path, bool $createDir = false): void {
            $fullpath = $this->path . \Path::normalise($path);

            if($createDir) {
                if(\Path::isFile($fullpath)) {
                    $dir = dirname($fullpath);

                    mkdir($dir, \Path::getPermissions($this->path), true);
                }
                else if(\Path::exists($fullpath)) {
                    throw new IOException("Path '$fullpath' is already a directory.");
                }
            }

            \Path::touch($path);
        }
        
        /**
         * Open another directory within the current directory.
         *
         * @param  mixed $path Must be relative to the parent directory.
         * @param  mixed $mode
         * @return void
         */
        public function openDirectory(string $path, bool $create = false): static {
            $path = $this->path . \Path::normalise($path);

            $dir = dirname($path);

            if(!\Path::exists($path) && $create)
                mkdir($dir, \Path::getPermissions($dir), true);

            return (new Directory($path));
        }

        /**
         * Delete a file within the current directory.
         *
         * @param  mixed $path Must be relative to directory.
         * @return void
         */
        public function deleteFile(string $path = null): bool {
            $path = $this->path.\Path::normalise($path);

            if(\Path::exists($path)) {
                if(\Path::isDirectory($path)) {
                    return rrmdir($path);
                }
                else if(\Path::isFile($path)) {
                    return unlink($path);
                }
            }
        }

        /**
         * @see rrmdir
         */
        public function delete(bool $symlinks = false): bool {
            $this->close();

            return rrmdir($this->path);
        }
        
        /**
         * Check if a file or directory exists within the current directory.
         *
         * @param  mixed $path
         * @return bool
         */
        public function has(string $path): bool {
            return \Path::exists($this->path.\Path::normalise($path));
        }

        /**
         * Check if a file exists within the current directory.
         *
         * @param  mixed $path
         * @return bool
         */
        public function hasFile(string $path): bool {
            return \Path::isFile($this->path.\Path::normalise($path));
        }

        /**
         * Check if a directory exists within the current directory.
         *
         * @param  mixed $path
         * @return bool
         */
        public function hasDirectory(string $path): bool {
            return \Path::isDirectory($this->path.\Path::normalise($path));
        }
     
        /**
         * Open the directory.
         *
         * @throws IOException           If the path is a file, the file doesn't exist; or there is an error openinng the directory.
         * 
         * @param  mixed $create Create if the directory doesn't exist.
         * @return void
         */
        public function open(bool $create = false): void {
            if(!\Path::exists($this->path)) {
                if($create) {
                    $this->create();
                }
                else {
                    throw new IOException([$this->path], IOException::ERROR_DIR_NOT_FOUND);
                }
            }
            else if(\Path::isDirectory($this->path)) {
                if(($this->resource = opendir($this->path)) === false) {
                    throw new IOException([$this->path], IOException::ERROR_DIR_OPEN_FAILURE);
                }
            }
            else {
                throw new IOException([$this->path], IOException::ERROR_DIR_NOT_FOUND);
            }
        }

        /**
         * @see readdir
         */
        protected function read(): string|false {
            return readdir($this->resource);
        }

        /**
         * Iterate through files and directories within the parent directory.
         * Doesn't inclode dotlinks.
         * 
         * @return Generator
         */
        public function walk(): Generator {
            if($this->resource !== NULL) {
                while(($name = $this->read()) !== FALSE) {
                    if(!\Str::isDotlink($name)) {
                        $fullpath = $this->path . "/" . $name;

                        yield array_merge(
                            \Path::info($fullpath),
                            [
                                "fullpath" => $fullpath,
                                "is_file" => \Path::isFile($fullpath),
                                "is_directory" => \Path::isDirectory($fullpath)
                            ]
                        );
                    }
                }
            }
        }
        
        /**
         * Rename a file or directory within the parent directory.
         * 
         * @throws IOException If there was an IO error while renaming or the directory isnt open.
         *
         * @param  mixed $source
         * @param  mixed $destination
         * @return void
         */
        public function rename(string $source, string $destination): void {
            if($this->resource !== NULL) {
                $sourcePath      = $this->path . "/" . $source;
                $destinationPath = $this->path . "/" . $destination;

                if(!rename($sourcePath, $destinationPath)) {
                    throw new IOException(["from" => $source, "to" => $destination], IOException::ERROR_RENAME_FAILURE);
                }
            }
            else {
                throw new IOException([$this->path], IOException::ERROR_DIR_OPEN_ASSERTION);
            }
        }

        public function close(): void {
            if($this->resource !== NULL) {
                closedir($this->resource);

                $this->resource = null;
            }
        }

        public function getFullPath(string $relativePath): string {
            return $this->path . \Path::normalise($relativePath);
        }
    }
}

?>