<?php

namespace Slate\IO {

    use Closure;

    class FileRotator {
        protected ?Closure $mutator = null;
        protected ?Closure $filter = null;

        public function __construct(?Closure $mutator = null, ?Closure $filter = null) {
            $this->mutator = $mutator;
            $this->filter  = $filter;
        }

        public function mutate(string $path, array $variables): string {
            if($this->mutator)
                return ($this->mutator)($path, $variables);

            $i = $variables["i"];
    
            return "$path".($i !== 0 ? ".".$i : "");
        }
    
        public function filter(string $path): bool {
            if($this->filter)
                return ($this->filter)($path);

            return true;
        }
    
        public function rotate(string $path): string {
            $index = 0;
    
            while(\Path::exists($available = $this->mutate($path, ["i" => $index])) ? $this->filter($available) : false)
                $index++;
    
            return $available;
        }
    }
}

?>