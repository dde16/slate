<?php

namespace Slate\IO {

    use Generator;
    use Slate\Exception\PathNotFoundException;
    use Slate\Exception\IOException;

    use Slate\Http\HttpEnvironment;

    use Slate\IO\Mime;
    use SplFileInfo;

    class File extends Stream {
        public SplFileInfo $path;
        public string $basename;
        public string $filename;
        public string $directory;

        protected bool $lock;

        protected ?StreamMode $predefinedMode = null;
        protected ?StreamMode $currentMode    = null;

        public function __construct(string|SplFileInfo $path, string $mode = null) {
            $this->path = is_string($path) ? (new SplFileInfo($path)) : $path;

            if($mode)
                $this->predefinedMode = new StreamMode($mode);
        }

        public function isWritable(bool $physically = false): bool {
            return $physically ? $this->path->isWritable() : ($this->currentMode ?? $this->predefinedMode)->isWritable();
        }

        public function isTruncatable(bool $physically = false): bool {
            return $physically
                ? $this->isWritable(true)
                : ($this->currentMode ?? $this->predefinedMode)->isTruncatable();
        }

        public function isReadable(bool $physically = false): bool {
            return $physically
                ? $this->path->isReadable()
                : ($this->currentMode ?? $this->predefinedMode)->isReadable();
        }

        public function isEof(): bool {
            $pointer = true;
            
            return is_bool($pointer) ? ($pointer ? ($this->tell() >= ($this->getSize())) : feof($this->resource)) : $pointer >= ($this->getSize());
        }

        public function getMode(): ?StreamMode {
            return $this->currentMode ?: $this->predefinedMode;
        }

        public function open(string $mode = null, int $lock = null): void {
            if($this->resource !== NULL)
                throw new IOException("File is already open.");

            if($this->predefinedMode !== null && $mode !== null)
                throw new IOException("Cannot set a mode for a file with the mode predefined on its creation.");

            if($mode === null)
                throw new IOException("No mode was specified when opening the file.");

            $this->currentMode = $mode = ($this->predefinedMode === null ? (new StreamMode($mode)) : $this->predefinedMode);

            $read = $mode->toString() !== "r" && $mode->toString() !== "r+";
            $exists = $this->path->isFile();
            
            if($read && !$exists) {
                \Path::touch($this->path);
            }
            else if($this->path->isDir()) {
                throw new IOException([$this->path->__toString()], IOException::ERROR_FILE_IS_DIR_MISMATCH);
            }

            $resource = fopen($this->path, $this->currentMode);

            if($resource === FALSE)
                throw new IOException([$this->path->__toString()], IOException::ERROR_FILE_OPEN_FAILURE);

            $this->resource = $resource;
            
            if($lock !== null) {
                $this->lock = true;
                $this->lock($lock);
            }
        }

        public function delete(): bool {
            $this->close();
            
            return unlink($this->path);
        }

        public function isLocked(): bool {
            $isLocked = 0;

            if(flock($this->resource, LOCK_EX|LOCK_NB, $isLocked))
                $this->unlock();

            return intval($isLocked);
        }

        public function lock(int $operation = LOCK_EX|LOCK_NB): void {
            $isLocked = 0;

            if(!flock($this->resource, $operation, $isLocked)) {
                if($isLocked)
                    throw new IOException([$this->path->__toString()], IOException::ERROR_LOCK_CONFLICT);
                
                throw new IOException([$this->path->__toString()], IOException::ERROR_LOCK_FAILURE);
            }
        }

        public function unlock(): void {
            $this->assertOpen();

            if(!flock($this->resource, LOCK_UN))
                throw new IOException([$this->path->__toString()], IOException::ERROR_UNLOCK_FAILURE);
        }

        public function close(): bool {
            $closed = parent::close();

            if($this->resource && $this->lock)
                $this->unlock();

            $this->resource = null;

            return $closed;
        }

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
            while(!$this->isEof()) {
                yield $this->readUntil($delimiter);
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

            if(copy($sourcePath, $destinationPath)) {
                if($follow) {
                    $this->path = new SplFileInfo($destinationPath);
                }
                else {
                    return (new File($destinationPath));
                }
            }
            else {
                throw new IOException("Unable to copy file '{$sourcePath}' to '{$destinationPath}'.");
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

        public function getExtensionMime(): string|null  {
            return static::getExtensionMimeOf($this->path->__toString());
        }

        public function getSignatureMime(): string|null {
            return static::getSignatureMimeOf($this->path->__toString());
        }

        /** Static */
        public static function getExtensionMimeOf(string $path): ?string {
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
            if(!HttpEnvironment::isLinux())
                throw new \BadFunctionCallException("You are not on a Linux system thereby the 'file' binary doesn't exist.");

            if(is_file($path)) {
                $proc = new Process(dirname($path));
                $proc->open("file", "-b", "--mime-type", $path);

                $mime = $proc->stdout->read();

                $proc->close();

                return $mime;
            }
            else {
                throw new IOException([$path], IOException::ERROR_FILE_NOT_FOUND);
            }

            return null;
        }

        public static function getSizeOf(string $path): int|false {
            \Path::assertFileExists($path);

            return filesize($path);
        }

        public static function getContentsOf(string $path): string {
            \Path::assertFileExists($path);

            if(($contents = file_get_contents($path)) === FALSE)
                throw new IOException("Unable to get contents for file at path '{$path}'.", IOException::ERROR_FILE_OPEN_FAILURE);

            return $contents;
        }
    }
}

?>