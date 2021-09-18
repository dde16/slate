<?php

namespace Slate\Exception {  
    class ImageException extends SlateException {
        public $code        = 1013;
        public $format      = "Unable to apply operation '{operation}' to image.";
    }
}


?>