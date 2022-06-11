<?php declare(strict_types = 1);

namespace Slate\Mvc\Result {

    use Slate\Http\HttpResponse;
    use Slate\IO\Mime;

    use Slate\IO\File;

    use Slate\Mvc\Env;

    class FileResult extends CommandResult {
        protected File $file;

        public function __construct(string $path, string $mime = null, bool $bypass = true) {
            $this->file = new File($path);
            $this->mime = $mime ?? $this->file->getExtensionMime() ?? "text/plain";

            parent::__construct($bypass);
        }

        public function modify(HttpResponse &$response): void {
            if(!$this->file->isOpen())
                $this->file->open("r");

            $response->headers["Content-Type"] = $this->mime;
            $this->file->pipe($response->getBody());
            $this->file->close();
        }
    }
}

?>