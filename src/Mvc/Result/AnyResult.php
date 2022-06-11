<?php declare(strict_types = 1);

namespace Slate\Mvc\Result {

    use Slate\Mvc\Result;
    use Slate\Mvc\ResultFactory;

    class AnyResult extends DataResult {
        protected DataResult $result;

        public function __construct(mixed $data, bool $bypass = false) {
            $this->result = $data !== null ? ResultFactory::create(\Any::getType($data, tokenise: true), [$data]) : (new ScalarResult(""));

            parent::__construct($bypass);
        }

        public function getMime(): string {
            return $this->result->getMime();
        }

        public function toString(): string {
            return $this->result->toString();
        }
    }
}

?>