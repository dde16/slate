<?php

namespace Slate\IO {

    use Generator;
    use Slate\Exception\PathNotFoundException;
    use Slate\Exception\IOException;

    use Slate\Http\HttpEnvironment;

    use Slate\IO\Mime;

    class File extends Stream {
        /** Contains all file modes which truncate. */
        const TRUNCATES = [
            File::WRITE_ONLY,
            File::WRITE_PLUS,

            File::WRITE_EXCLUSIVE,
            File::READ_WRITE_EXCLUSIVE
        ];

        /** Contains all file modes which read. */
        const READS = [
            File::READ_ONLY,
            File::READ_WRITE,
            File::READ_WRITE_EXCLUSIVE,
            File::WRITE_PLUS,
            File::APPEND_SEEKABLE
        ];

        const READ_ONLY            = "r";
        const READ_WRITE           = "r+";
        const READ_WRITE_EXCLUSIVE = "x+";
        
        const WRITE_ONLY           = "w";
        const WRITE_EXCLUSIVE      = "x";
        const WRITE_PLUS           = "w+";

        const APPEND               = "a";
        const APPEND_SEEKABLE      = "a+";

        // 10 Mebibytes
        const MAX_SIZE = ((1024 ** 3) * 2);

        // File
        public string $path;
        public    string $basename;
        public    string $filename;
        public    string $directory;
        protected ?string $predefinedMode = null;
        protected ?string $currentMode = null;

        public function __construct(string $path, string $mode = null) {
            $this->path = $path;
            $info = \Path::info($path);

            $this->basename = $info["basename"];
            $this->filename = $info["filename"];
            $this->predefinedMode = $mode;
            $this->directory = $info["directory"];
        }

        public function __destruct() {
            $this->close();
        }

        public function isEof(): bool {
            $pointer = true;
            
            return is_bool($pointer) ? ($pointer ? ($this->tell() >= ($this->getSize())) : feof($this->resource)) : $pointer >= ($this->getSize());
        }

        public function open(string $mode = null): void {
            if($this->predefinedMode !== null && $mode !== null)
                throw new IOException("Cannot set a mode for a file with the mode predefined on its creation.");

            if($mode === null)
                throw new IOException("No mode was specified when opening the file.");

            $this->currentMode = $mode = $this->predefinedMode ?: $mode;

            if($this->resource !== NULL)
                throw new IOException("File is already open.");

            $read = $mode !== "r" && $mode !== "r+";
            $exists = \Path::exists($this->path);
            
            if($read && !$exists) {
                File::touch($this->path);
            }
            else if (\Path::isDir($this->path)) {
                throw new \Exception(
                    \Str::format(
                        "Path '{path}' is a directory.", $this->path
                    )
                );
            }
            // else if(!$read) {
            //     $this->size = File::getSizeOf($this->path);
            // }

            // if($this->size >= File::MAX_SIZE) {
            //     throw new \Exception(
            //         \Str::format(
            //             "File at '{path}' is larger than the maximum size limit.", $path
            //         )
            //     );
            // }

            $this->mode = $mode;

            $resource = fopen($this->path, $this->mode);

            if($resource === FALSE) {
                throw new IOException(
                    \Str::format(
                        "Unable to open file at '{}'.",
                        $this->path
                    )
                );
            }

            $this->resource = $resource;

            // if(!flock($fp, LOCK_EX|LOCK_NB, $isLocked)) {
            //     if($isLocked) {
            //         throw new IOException(
            //             \Str::format("Unable to lock file '{}' as another process holds to lock.", $this->path)
            //         );

            //         $this->close();
            //     }
            //     else {
            //         throw new IOException(
            //             \Str::format(
            //                 "An unkown error occured while obtaining the lock for file '{}'.",
            //                 $this->path
            //             )
            //         );
            //     }
            // }
        }

        public function delete(): bool {
            $this->close();
            
            return unlink($this->path);
        }

        // public function close(): bool {
        //     parent::close();

        //     // flock($this->resource, LOCK_UN);

        //     $this->resource = null;
        // }

        public function getSize(): int {
            $this->assertOpen();

            return intval(fstat($this->resource)["size"]);
        }

        public function writeline(string $data, string $delimiter = "\r\n"): void {
            $this->write($data.$delimiter);
        }

        public function readlines(int $count = 1, string $delimiter = "\r\n", string $encoding = null): Generator {
            $lines = [];
            $index = 0;

            while(($index < $count)) {
                if($line = $this->readline($delimiter, $encoding)) {
                    yield $line;
                }
                else {
                    break;
                }

                $index++;
            }
        }

        public function split(string $delimiter = "\r\n"): Generator {
            while(!$this->isEof() ? ($data = $this->readUntil($delimiter) ?: $this->read()) : false) {
                yield $data;
            }
        }

        public function readline(string $delimiter = "\n", string $encoding = null): string|null {
            $line = $this->readUntil($delimiter) ?: $this->read();

            if($encoding !== null) {
                $encoding = \Str::lower($encoding);
                $detectEncoding = \Str::lower(mb_detect_encoding($line));

                if($detectEncoding !== $encoding && !empty($detectEncoding)) {
                    $line = mb_convert_encoding($line, $detectEncoding, $encoding);
                }
            }

            return $line;
        }

        public function copy(string $destination, bool $follow = true): File|null {
            if($this->isOpen())
                throw new IOException("Cannot copy a file while it is open.");

            $sourcePath = $this->path;
            $destinationPath = $destination;

            if(!\Str::isPath($destination)) {
                $sourcePathDirectory = dirname($sourcePath);

                $destinationPath = $sourcePathDirectory . $destination;
            }

            if(copy($sourcePath, $destinationPath)) {
                if($follow) {
                    $this->path = $destinationPath;
                }
                else {
                    return (new File($destinationPath));
                }
            }
            else {
                throw new IOException(
                    \Str::format("Unable to copy file '{}' to '{}'.", $sourcePath, $destinationPath, $this->path)
                );
            }

            return null;
        }

        

        public function rename(string $destination, bool $follow = true): void {
            if($this->isOpen()) {
                throw new IOException("Cannot rename a file while it is open.");
            }

            $sourcePath = $this->path;
            $destinationPath = $destination;

            if(!\Str::isPath($destination)) {
                $sourcePathDirectory = dirname($sourcePath);

                $destinationPath = $sourcePathDirectory . $destination;
            }

            if(rename($sourcePath, $destinationPath)) {
                if($follow) $this->path = $destinationPath;
            }
            else {
                throw new IOException(
                    \Str::format("Unable to rename file '{}' to '{}'.", $sourcePath, $destinationPath, $this->path)
                );
            }
        }

        public function getPath(): string {
            return $this->path;
        }

        public function getExtensionMime(): string|null  {
            return static::getExtensionMimeOf($this->path);
        }

        public function getSignatureMime(): string|null {
            return static::getSignatureMimeOf($this->path);
        }

        /** Static */
        public static function getExtensionMimeOf(string $path): string {
            $pathInfo = \Path::info($path);

            if($extension = $pathInfo["extension"]) {
                if(!\Str::isEmpty($extension)) {
                    $mime = Mime::mimeByExtension($extension);

                    if($mime !== NULL) {
                        return $mime;
                    }
                }
            }

            return null;
        }

        public static function getSignatureMimeOf(string $path): string {
            // file -b --mime-type image.png

            if(!HttpEnvironment::isLinux()) {
                throw new \BadFunctionCallException("You are not on a Linux system thereby the 'file' binary doesn't exist.");
            }

            if(\Path::isFile($path)) {
                $proc = new Process(dirname($path));
                $proc->open("file", "-b", "--mime-type", $path);

                $mime = $proc->stdout->read();

                $proc->close();

                return $mime;
            }
            else {
                throw new IOException(["path" => $path], IOException::ERROR_FILE_NOT_FOUND);
            }

            return null;
        }

        public static function exists(string $path): bool {
            return file_exists($path);
        }

        public static function touch(string $path): void {
            $resource = fopen($path, File::WRITE_ONLY);

            if($resource === FALSE) {
                throw new IOException(\Str::format(
                    "Unable to open to touch file at '{}'.",
                    $path
                ));
            }

            fclose($resource);
        }

        public static function getSizeOf(string $path): int|false {
            if(!File::exists($path))
                throw new IOException(["path" => $path], IOException::ERROR_FILE_NOT_FOUND);

            return filesize($path);
        }

        public static function getContentsOf(string $path): string {
            if(!\Path::exists($path)) {
                throw new IOException(["path" => $path], IOException::ERROR_FILE_NOT_FOUND);
            }

            if(($contents = file_get_contents($path)) === FALSE)
                throw new IOException(\Str::format(
                    "Unable to get contents for file at path '{}'.",
                    $path
                ), IOException::ERROR_FILE_OPEN_FAILURE);

            return $contents;
        }
    }
}

?>