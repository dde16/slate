<?php

namespace Slate\Mvc\Result {
    use Slate\Http\HttpResponse;
    use Slate\Http\HttpCode;

    class RedirectResult extends CommandResult {
        protected string $path;
        protected string $mode;

        public function __construct(string $path, string $mode = "temporary") {
            $this->path = $path;
            $this->mode = "temporary";
        }

        public function modify(HttpResponse &$response): void {
            $response->status              = $this->mode == "temporary" ? HttpCode::FOUND : HttpCode::MOVED_PERMANENTLY;
            $response->headers["Location"] = $this->path;
        }
    }
}

?>