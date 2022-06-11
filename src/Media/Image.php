<?php declare(strict_types = 1);

namespace Slate\Media {

    use BadFunctionCallException;
    use Slate\IO\File;

    use Slate\IO\Stream;
    use Slate\IO\StreamReader;
    
    use Slate\IO\Buffer;

    use Slate\Media\ImageFilter;
    use Slate\Media\ImageType;

    use Slate\Exception\ParseException;
    use Slate\Exception\ImageException;
    use Slate\Exception\PathNotFoundException;

    use InvalidArgumentException;
    use Slate\Data\Collection;
    use Slate\Exception\IOException;

    class Image {
        /**
         * The gd image resource.
         *
         * @var mixed
         */
        public mixed      $resource;

        /**
         * The list of colours used in the image.
         *
         * @var Collection
         */
        public Collection $colours;

        public function __construct() {
            $this->resource = null;
            $this->colours  = collect();
        }

        public function crop(int $x, int $y, int $width, int $height, bool $inplace = true): ?Image {
            $resource = imagecrop(
                $this->resource,
                ["x" => $x, "y" => $y, "width" => $width, "height" => $height]
            );

            if($resource === false) {
                throw new ImageException(\Str::format(
                    "Unable to crop from [x: {}, y: {}] by [w: {}, h: {}]",
                    $x, $y,
                    $width, $height
                ));
            }

            if(!$inplace) {
                $image = new Image();
                $image->resource = $resource;

                return $image;
            }

            $this->resource = $resource;

            return null;
        }

        public static function fontFile(string $path): int {
            if(file_exists($path)) {
                $font = imageloadfont($path);

                if($font === FALSE)
                    throw new ParseException("Unable to load font at location '{$path}'.");

                return $font;
            }
            else {
                throw new IOException(["path" => $path], IOException::ERROR_FILE_NOT_FOUND);
            }
        }

        public function create(int $width, int $height): void {
            $resource = imagecreate($width, $height);

            if($resource === false)
                throw new ImageException("Unable to create the image by size $width x $height");

            $this->resource = $resource;
        }

        public function getResource(): mixed {
            return $this->resource;
        }

        public function getDimensions(): array {
            return [$this->getWidth(), $this->getHeight()];
        }

        public function getHeight(): int {
            return imagesy($this->resource);
        }

        public function getWidth(): int {
            return imagesx($this->resource);
        }

        public function allocate(): void {
            $arguments      = func_get_args();
            $argumentsCount = func_num_args();

            if($argumentsCount === 1) {
                throw new BadFunctionCallException();
            }

            $name = $arguments[0];

            $arguments = \Arr::slice($arguments, 1);
            $argumentsCount--;

            if($argumentsCount === 1) {
                $colour = $arguments[0];
                $colourType = \Any::getType($colour);

                switch($colourType) {
                    case "string":
                        break;
                    case "int":
                        $red = $colour;
                        $green = $colour;
                        $blue = $colour;
                        break;
                }
            }
            else if($argumentsCount === 3) {
                if(\Arr::all($arguments, fn($value) => is_int($value))) {
                    list($red, $green, $blue) = $arguments;
                }
                else {
                    throw new InvalidArgumentException("All three colours must be non-zero positive integers.");
                }
            }
            else {
                throw new InvalidArgumentException("There must be three colours.");
            }

            $this->colours[$name] = imagecolorallocate($this->resource, $red, $green, $blue);
        }

        public function getTextBoundingBox(
            float $size,
            float $angle,
            string $fontfile,
            string $text
        ): array {
            if(!file_exists($fontfile))
                throw new IOException(["path" => $fontfile], IOException::ERROR_FILE_NOT_FOUND);

            $boundingBox = imagettfbbox(
                $size,
                $angle,
                $fontfile,
                $text
            );

            if($boundingBox === FALSE) {
                throw new ImageException(
                    "Unable to get the bounding box for text."
                );
            }

            $boundingBox = \Arr::chunk($boundingBox, 2);
            

            $sizes = [
                "width"  => $boundingBox[1][0] - $boundingBox[0][0],
                "height" => abs($boundingBox[2][1] - $boundingBox[0][1])
            ];

            $boundingBox = \Arr::key($boundingBox, [
                0 => "bottom-left",
                1 => "bottom-right",
                2 => "top-left",
                3 => "top-right"
            ]);

            return ["boundaries" => $boundingBox, "size" => $sizes];
        }

        public function putText(
            string $text,
            float $size,
            string $colourName,
            float $angle,
            int $x,
            int $y,
            string $fontfile,
            string $anchor = "centre"
        ): void {
            list(
                $textBoundingBox,
                $textSizing
            ) = \Arr::values($this->getTextBoundingBox(
                $size,
                $angle,
                $fontfile,
                $text
            ));

            if(is_file($fontfile)) {
                if($this->colours->has($colourName)) {
                    $colour = $this->colours[$colourName];

                    switch($anchor) {
                        case "centre":
                            $x -= ($textSizing["width"] / 2);
                            $y += ($textSizing["height"] / 2);
                            break;
                        case "bottom-left":
                            break;
                        case "bottom-right":
                            $x -= $textSizing["width"];
                            break;
                        case "top-left":
                            $y += $textSizing["height"];
                            break;
                        case "top-right":
                            $x -= $textSizing["width"];
                            $y += $textSizing["height"];
                            break;
                    }

                    $x = intval($x);
                    $y = intval($y);

                    imagettftext(
                        $this->resource,
                        $size,
                        $angle,
                        $x,
                        $y,
                        $colour,
                        $fontfile,
                        $text
                    );
                }
                else {
                    throw new InvalidArgumentException(
                        \Str::format(
                            "Unkown colour '{}'.",
                            $colourName
                        )
                    );
                }
            }
            else {
                throw new IOException("Unable to find font file '{$fontfile}'.", IOException::ERROR_FILE_NOT_FOUND);
            }
        }

        /** Filling */
        public function fill(string $colourName, int $x = null, int $y = null): void {
            $x = ($x ?: $this->getWidth())-1;
            $y = ($y ?: $this->getHeight())-1;

            if(!$this->colours->has($colourName)) 
                throw new ImageException(\Str::format("Unkown colour '{}'.", $colourName));

                
            $colour = $this->colours[$colourName];

            if(($status = imagefill($this->resource, $x, $y, $colour)) === FALSE)
                throw new ImageException("Unable to fill image with colour '$colourName'.");
        }

        public function assertIsset(): void {
            if($this->resource === null)
                throw new ImageException("The image is not loaded with a valid resource.");
        }

        /* Scaling */
        public function scaleBy(int|float $scale): void {
            $this->assertIsset();

            $width = imagesx($this->resource) * $scale;
            $height = imagesy($this->resource) * $scale;

            $this->scaleTo($width, $height);
        }

        public function scaleTo(int $width, int $height): void {
            $this->assertIsset();

            $resource = imagescale($this->resource, $width, $height);

            if($resource === FALSE) {
                throw new ImageException(
                    \Str::format(
                        "Unable to scale this image to {w}x{h}",
                        $width, $height
                    )
                );
            }

            $this->resource = $resource;
        }

        public function scaleToWidth(int $width): void {
            $this->assertIsset();

            list($originalWidth, $originalHeight) = $this->getDimensions($this->resource);

            $scale    = $width/$originalWidth;
            $nuWidth  = $width;
            $nuHeight = intval($scale * $originalHeight);

            $this->scaleTo($nuWidth, $nuHeight);
        }

        public function scaleToHeight(int $height): void {
            $this->assertIsset();

            list($originalWidth, $originalHeight) = $this->getDimensions($this->resource);

            $scale    = $height / $originalHeight;
            $nuHeight = $height;
            $nuWidth  = intval(round($scale * $originalWidth));

            $this->scaleTo($nuWidth, $nuHeight);
        }

        /* Functions */

        public function brightness(int $value): void {
            if($value >= -255 && $value <= 255) {
                $this->resource = imagefilter($this->resource, ImageFilter::BRIGHTNESS, $value);
            }
        }

        public function greyscale(): void {
            $this->assertIsset();

            $this->resource = imagefilter($this->resource, ImageFilter::GREYSCALE);
        }

        public function grayscale(): void {
            $this->assertIsset();

            $this->resource = $this->greyscale();
        }

        public function contrast(int $value): void {
            $this->assertIsset();

            $this->resource = imagefilter($this->resource, ImageFilter::CONTRAST, $value);
        }

        public function meanRemoval(): void{
            $this->assertIsset();

            $this->resource = imagefilter($this->resource, ImageFilter::MEAN_REMOVAL);
        }

        public function smooth(int $value): void {
            $this->assertIsset();

            $this->resource = imagefilter($this->resource, ImageFilter::SMOOTH);
        }

        public function pixelate(int $blocksize, bool $mode = false): void {
            $this->assertIsset();

            $this->resource = imagefilter($this->resource, ImageFilter::PIXELATE, $blocksize, $mode);
        }

        public function colorize(int $red, int $green, int $blue, int $alpha): void {
            $this->assertIsset();

            $this->resource = imagefilter($this->resource, ImageFilter::COLORIZE, $red, $green, $blue, $alpha);
        }

        public function exportBuffer(int $type = ImageType::JPEG, array $arguments = []): void {
            $this->assertIsset();

            if(!(imagetypes() & $type)) {
                throw new InvalidArgumentException("Invalid type supplied for Image->exportString(int).");
            }

            
            $function = ImageType::getFunction($type);


            \Fnc::call($function, [$this->resource, ...$arguments]);
        }

        public function exportString(int $type = ImageType::JPEG, array $arguments = []): string {
            $this->assertIsset();

            ob_start();
            $this->exportBuffer($type, $arguments);
            $data = ob_get_contents();
            ob_end_clean();

            return $data;
        }

        public function exportBase64(int $type = ImageType::JPEG, bool $html = false): string {
            $this->assertIsset();

            if(!(imagetypes() & $type)) {
                throw new InvalidArgumentException("Invalid type supplied for Image->exportBase64(int).");
            }

            $base64 = base64_encode($this->exportString($type));

            if($html)
                $base64 = "data:".(ImageType::getMime($type)).";charset=utf-8;base64,".$base64;
            
            return $base64;
        }

        public function import($source): void {
            $type = \Any::getType($source);

            switch($type) {
                case "string":
                    $this->importPath($source);
                    break;
                case "resource":
                    $this->importStream($source);
                    break;
                case Stream::class:
                    $this->importStream($source);
                    break;
                case File::class:
                    $this->importFile($source);
                    break;
                default:
                    throw new InvalidArgumentException(
                        "An import method was not found for type '$type'."
                    );
                    break;
            }
        }

        public function importPath(string $path): void {
            if(file_exists($path)) {
                $file = new File($path);
                $file->open("r");

                $this->importStream($file);

                $file->close();
            }
            else {
                throw new IOException(["path" => $path], IOException::ERROR_FILE_NOT_FOUND);
            }
        }
        
        public function importString(string $data): void {
            $resource = imagecreatefromstring($data); 

            if($resource === FALSE) {
                throw new ParseException(
                    "Unable to create the image from the string passed."
                );
            }

            $this->resource = $resource;
        }

        public function importStream($resource): void {
            $data = NULL;

            if(is_resource($resource)) {
                $stream = new Stream($resource);
                $data = $stream->read();
                $stream->close();
            }
            else if(\Cls::isSubclassOf($resource, [Stream::class, StreamReader::class])) {
                $data = $resource->read();
            }

            if($data !== NULL) {
                $this->importString($data);
            }
        }

        public function importFile(File $file): void{
            if(!$file->isOpen()) {
                $file->open("r");
            }

            $data = $file->read();
            $file->close();

            $this->importString($data);
        }
    }
}



?>