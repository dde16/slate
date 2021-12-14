<?php

namespace Slate\Media {

    use Slate\Data\BasicArray;
    use Slate\Data\IStringForwardConvertable;
    use Slate\Data\Iterator\ArrayExtendedIterator;
    use Slate\Data\TStringNativeForwardConvertable;
    use Slate\Exception\ParseException;

/**
     * A class that, much alike the FilePathInfo SPL object, handles paths.
     * However, this is for applications where the logic of dotlinks etc
     * is still maintained while not being able to touch the filesystem.
     * 
     * This is useful for web-based projects such as webspiders.
     */
    class UriPath extends BasicArray implements IStringForwardConvertable {
        use TStringNativeForwardConvertable;

        public array $items = [];
        public bool $relative = false;

        public function __construct(string $path, bool $assert = false) {
            $this->setPath($path, $assert);
        }

        public function hasExtension(): bool {
            return \Str::contains($this->getBasename(), ".");
        }

        public function setExtension(string $extension): void {
            $filename = $this->getFilename();

            $this->items[array_key_last($this->items)] = "{$filename}.{$extension}";
        }

        /**
         * The path setter which simulatenously resolves as many dotlinks as possible.
         *
         * @param string $path
         * @param bool   $assertReachable If a dotlink cannot go travel further (eg. /somedir/../../file.txt ->/../file.txt) then it will raise an error.
         *
         * @return void
         */
        public function setPath(string $path, bool $assert = false): void {
            $this->relative = $path[0] !== "/" && !\Str::startswith($path, ".");

            $iterator = new ArrayExtendedIterator(\Str::split(\Str::trimAffix($path, "/"), "/"));

            $prevIndex = null;

            /**
             * This block contains the logic for resolving dotlinks.
             * 
             * Taking the example:
             * /root/path/to/../../file
             * 
             * Converted into a list of segments
             * root, path, to, .., .., file
             * 
             * Each time a dotlink (..) is encounted, the cursor will eliminated the closest segment to the left then itself.
             * root, path, to  , ..  , ..  , file
             * root, path, null, null, ..  , file
             * root, null, null, null, null, file
             * 
             * Finally nulls can be ignored which gives us our final path
             * /root/file
             * 
             * If there are any dotlinks left, this will be raised as an error.
             */
            while($iterator->valid()) {
                $currentIndex = $iterator->key();
                $currentItem = $iterator->current();

                if($currentItem === "..") {
                    $iterator->anchor();

                    $prevItem = true;

                    if($iterator->key() !== 0) {
                        if($prevIndex === null && $iterator->key() !== 0) {
                            do {
                                $iterator->prev();
        
                                $prevIndex = $iterator->key();
                                $prevItem = $iterator->current();
        
                            } while($prevIndex > 0  && $prevItem === null);
                        }
                        else if(--$prevIndex < 0) {
                            $prevItem = null;
                        }

                        if($prevItem !== null && $prevItem !== "..") {
                            $iterator->offsetSet($prevIndex, null);
                            $iterator->offsetSet($currentIndex, null);
                        }

                        $prevItem = null;
                        
                        $iterator->revert();
                    }
                }
                else if($currentItem === ".") {
                    $iterator->offsetSet($currentIndex, null);
                }
                else {
                    $prevIndex = null;
                }

                $iterator->next();
            }


            $this->items = \Arr::values(\Arr::filter($iterator->getArrayCopy()));

            if($this->items[0] === ".." && $assert)
                throw new ParseException("Path {$path} resolved to {$this->toString()} with unreachable dotlinks.");
        }

        public function hasDotlinks(): bool {
            return \Arr::any($this->items, fn($v) => $v === "..");
        }

        public function apply(string|UriPath $path): void {
            $path = is_string($path) ? new UriPath($path) : $path;

            $this->setPath(($path->relative || $path->hasDotlinks()) ? $this->toString().$path->toString() : \Path::normalise($path->toString()));
        }

        public function getDirectory(): ?string {
            $diritems = \Arr::slice($this->items, 0, -1);

            return !\Arr::isEmpty($diritems) ? ((!$this->relative ? "/" : "").\Arr::join($diritems, "/")) : false;
        }

        /**
         * Gets the filename and extension.
         *
         * @return string|null
         */
        public function getBasename(): ?string {
            return \Arr::last($this->items);
        }

        /**
         * Get the extension (specifically all after the last dot).
         *
         * @return string|null
         */
        public function getExtension(): ?string {
            return \Str::afterLast($this->getBasename(), ".");
        }

        /**
         * Get filename before the last dot.
         *
         * @return string|null
         */
        public function getFilename(): ?string {
            return \Str::beforeLast($this->getBasename(), ".");
        }

        public function toString(string $delimiter = "/"): string {
            return (!$this->relative ? $delimiter : "").\Arr::join($this->items, $delimiter);
        }
    }
}

?>