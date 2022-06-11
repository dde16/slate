<?php declare(strict_types = 1);

namespace Slate\Neat\Attribute {
    use Attribute;
    use Slate\Metalang\MetalangAttribute;

    #[Attribute(Attribute::TARGET_METHOD)]
    class Benchmark extends MetalangAttribute {    
        protected string $pipe;
    
        public function __construct(string $pipe = "stdout") {
            $this->pipe = $pipe;
        }
    
        public function pipe(float $timing): void {
            $message = \Str::format(
                "{}::{}() ran for {} seconds",
                $this->parent->getDeclaringClass()->getName(),
                $this->parent->getName(),
                \Str::repr($timing)
            );
    
            if($this->pipe === "stdout") {
                debug("[Benchmark] " . $message);
            }
            else {
                throw new \Error("Unknown benchmark pipe '".$this->pipe."'");
            }
        }
    }
    
}

?>