<?php

namespace Slate\Mvc\Result {
    use Slate\Data\IArrayForwardConvertable;

    class JsonResult extends DataResult {
        protected array|object $data;

        public function __construct(array|object $data, bool $bypass = false) {
            $this->data   = $data;
            $this->mime   = "application/json";
            parent::__construct($bypass);
        }

        public function toString(): string {
            if(is_object($this->data)) {
                if(\Cls::hasInterface($this->data, IArrayForwardConvertable::class)) {
                    $this->data = $this->data->toArray();
                }
            }

            return json_encode($this->data, JSON_PRETTY_PRINT);
        }
    }
}

?>