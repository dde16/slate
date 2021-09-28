<?php

namespace Slate\Media {

    use Slate\Exception\IOException;
    use Slate\IO\File;

    use Slate\Exception\ParseException;
    use Slate\Exception\PathNotFoundException;

    class ImageFont {
        public static function importFile(string $path): int|false {
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
    }
}

?>