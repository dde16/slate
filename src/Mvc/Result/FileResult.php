<?php

namespace Slate\Mvc\Result {

    use Slate\Http\HttpResponse;
    use Slate\IO\Mime;

    use Slate\IO\File;

    use Slate\Mvc\Env;

    class FileResult extends CommandResult {
        protected File $file;

        public function __construct(string $path, string $mime = null, bool $bypass = true) {
            $this->file = new File($path);
            $this->bypass = $bypass;
            $this->mime = ($mime ?: $this->file->getExtensionMime()) ?: "text/plain";
        }

        public function modify(HttpResponse &$response): void {
            if(!$this->file->isOpen())
                $this->file->open("r");

            $this->file->pipe($response->getBody());
            $this->file->close();
        }

        // protected function getContents() {
        //     $this->data->open("r");

        //     $data = $this->data->read();

        //     $this->data->close();

        //     return $data;
        // }

        // public function toString() {
        //     return $this->getContents();
        // }
    }
}

?>