<?php

namespace Slate\Mvc\Result {
    use Slate\Http\HttpResponse;

    use Slate\Mvc\Result;

    abstract class CommandResult extends Result  {
        protected bool $bypass = true;

        public abstract function modify(HttpResponse &$response): void;
    }
}

?>