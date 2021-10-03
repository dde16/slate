<?php

namespace Slate\Mvc\Result {

    use Slate\Mvc\Result;
    use Slate\Mvc\ResultFactory;

    class AnyResult extends DataResult {
        protected DataResult $result;

        public function __construct(mixed $data, bool $bypass = false) {
            $this->result = ResultFactory::create(\Any::getType($data, tokenise: true), [$data]);

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