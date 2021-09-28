<?php

namespace Slate\Mvc\Result {

    use Slate\Exception\IOException;
    use Slate\Exception\PathNotFoundException;
    
    use Slate\Mvc\Env;
    use Slate\IO\Mime;
    use Slate\IO\Buffer;

    class ViewResult extends DataResult {
        public function __construct(string $path, array $data = [], bool $bypass = false) {
            $this->mime = "text/html";
            $this->bypass = $bypass;

            if(!\Path::hasExtension($path)) {
                $path .= ".php";
            }

            $path = Env::get("mvc.view.path").\Path::normalise($path);
            $root = env("mvc.root.path");

            if(\Path::safe($root, $path)) {
                $this->data = Buffer::wrap(function() use($path, $data) {
                    $_DATA = $data;
                    unset($data);

                    require($path);

                    unset($_DATA);
                });
            }
            else {
                throw new IOException(["subPath" => $path, "rootPath" => $root], IOException::ERROR_UNSAFE_PATH);
            }
        }

        public function toString(): string {
            return $this->data;
        }
    }
}

?>