<?php declare(strict_types = 1);

namespace Slate\Media {
    use Slate\Structure\Enum;

    class ImageFilter extends Enum {
        const NEGATE         = IMG_FILTER_NEGATE;
        const GREYSCALE      = IMG_FILTER_GRAYSCALE;
        const GRAYSCALE      = IMG_FILTER_GRAYSCALE;
        const BRIGHTNESS     = IMG_FILTER_BRIGHTNESS;
        const CONTRAST       = IMG_FILTER_CONTRAST;
        const COLORIZE       = IMG_FILTER_COLORIZE;
        const EDGEDETECT     = IMG_FILTER_EDGEDETECT;
        const EMBOSS         = IMG_FILTER_EMBOSS;
        const GAUSSIAN_BLUR  = IMG_FILTER_GAUSSIAN_BLUR;
        const SELECTIVE_BLUR = IMG_FILTER_SELECTIVE_BLUR;
        const MEAN_REMOVAL   = IMG_FILTER_MEAN_REMOVAL;
        const SMOOTH         = IMG_FILTER_SMOOTH;
        const PIXELATE       = IMG_FILTER_PIXELATE;
        const SCATTER        = IMG_FILTER_SCATTER;
    }
}

?>