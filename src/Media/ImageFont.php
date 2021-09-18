<?php

namespace Slate\Media {
    use Slate\IO\File;

    use Slate\Exception\ParseException;
    use Slate\Exception\PathNotFoundException;

    class ImageFont {
        public static function importFile(string $path): int|false {
            if(File::exists($path)) {
                $font = imageloadfont($path);

                if($font === FALSE) {
                    throw new ParseException(
                        \Str::format(
                            "Unable to load font at location '{}'.",
                            [ $path ]
                        )
                    );
                }

                return $font;
            }
            else {
                throw new PathNotFoundException([
                    "path" => $path
                ]);
            }
        }
    }
}

?>