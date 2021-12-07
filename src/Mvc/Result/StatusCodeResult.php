<?php

namespace Slate\Mvc\Result {
    use Slate\Http\HttpResponse;
    use Slate\Http\HttpCode;

    class StatusCodeResult extends CommandResult {
        protected int $code;

        public function __construct(int $code) {
            parent::__construct();
            $this->code = $code;
        }

        public function modify(HttpResponse &$response): void {
            $response->status              = $this->code;
        }
    }
}

?>