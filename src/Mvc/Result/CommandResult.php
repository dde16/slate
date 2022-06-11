<?php declare(strict_types = 1);

namespace Slate\Mvc\Result {
    use Slate\Http\HttpResponse;

    use Slate\Mvc\Result;

    abstract class CommandResult extends Result  {
        public function __construct(bool $bypass = true) {
            parent::__construct($bypass);
        }

        public abstract function modify(HttpResponse &$response): void;
    }
}

?>