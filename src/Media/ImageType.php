<?php declare(strict_types = 1);

namespace Slate\Media {
    use Slate\Structure\Enum;

    class ImageType extends Enum {
        const WBMP = IMAGETYPE_WBMP;
        const GIF  = IMAGETYPE_GIF;
        const JPEG = IMAGETYPE_JPEG;
        const PNG  = IMAGETYPE_PNG;
        const WEBP = IMAGETYPE_WEBP;
        const XBM  = IMAGETYPE_XBM;

        private static $functions = [
            ImageType::WBMP => "imagewbmp",
            ImageType::GIF  => "imagegif",
            ImageType::JPEG => "imagejpeg",
            ImageType::PNG  => "imagepng",
            ImageType::WEBP => "imagewebp",
            ImageType::XBM  => "imagexbm"
        ];

        private static $mimes = [
            ImageType::WBMP => "image/vnd.wap.wbmp",
            ImageType::GIF  => "image/gif",
            ImageType::JPEG => "image/jpeg",
            ImageType::PNG  => "image/png",
            ImageType::WEBP => "image/webp",
            ImageType::XBM  => "image/x-xbitmap"
        ];

        public static function getFunction(int $imagetype): string|null {
            return static::$functions[$imagetype];
        }

        public static function getMime(int $imagetype): string|null {
            return static::$mimes[$imagetype];
        }
    }
}

?>