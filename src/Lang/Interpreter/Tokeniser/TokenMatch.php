<?php


namespace Slate\Lang\Interpreter\Tokeniser {
    use Slate\Data\Iterator\StringIterator;
    use Slate\IO\File;
    use Slate\Lang\Interpreter\ICodeable;

    final class TokenMatch {
        protected object     $code;
        public    int                 $id;
        public    string              $name;
        public    int                 $length;
        public    string              $value;
        
        public array $counters;
        
        public function __construct(
            object $code,
            int        $id,
            string     $name,
            int        $length,
            array      $counters
        ) {
            $this->code      = $code;
            $this->id        = $id;
            $this->name      = $name;
            $this->length    = $length;
            
            if($this->length === 0)
                throw new \Error(\Str::format(
                    "Length for token '{}' match cannot be zero, check your token regular expressions.",
                    $this->name
                ));
            
            $this->counters = $counters;
        }

        public function getValue() {
            $this->code->anchor();

            $this->code->seek($this->counters["pointer"]);
            $value = $this->code->read($this->length);

            $this->code->revert();

            return $value;
        }

        public function persistValue() {
            

            $this->value = $this->getValue();
        }

        public function __toString() {
            return \Str::format(
                "Token({}, '{}', {})",
                [
                    /**
                     * This is only really needed for debugging, should be removed
                     * in production to conserve memory.
                     **/
                    $this->name,
                    $this->getValue(),
                    \Arr::join(
                        \Arr::values(
                            \Arr::mapAssoc(\Arr::merge([
                                "length" => $this->length,
                            ], $this->counters),
                            function($name, $value) {
                                return [$name, "$name=".($value ?: 0)];
                            }
                        )
                    ), ", ")
                ]
            );
        }
    }
}

?>