<?php declare(strict_types = 1);

namespace Slate\Data {

    use ArrayAccess;

    class FieldPrepped extends Field {
        protected array $sources = [];
    
        public function from(ArrayAccess|array ...$sources): static {
            foreach($sources as $source)
                $this->sources[] = $source;
    
            return $this;
        }

        public function object(string $errorMessage = null): mixed {
            return parent::object($errorMessage)->get();
        }

        public function array(string $errorMessage = null): mixed {
            return parent::array($errorMessage)->get();
        }

        public function bool(string $errorMessage = null): mixed {
            return parent::bool($errorMessage)->get();
        }

        public function int(string $errorMessage = null): mixed {
            return parent::int($errorMessage)->get();
        }

        public function string(string $errorMessage = null): mixed {
            return parent::string($errorMessage)->get();
        }

        public function float(string $errorMessage = null): mixed {
            return parent::float($errorMessage)->get();
        }

    
        public function get(bool|string $assert = true): mixed {
            foreach(\Arr::describe($this->sources) as list($position, $source)) {
                list($sourced, $value) = $this->getFrom($source, boolval($position & \Arr::POS_END) ? $assert : false);
    
                if($sourced)
                    return $value;
            }
    
            return null;
        }
    }
}

?>