<?php

namespace Slate\Neat\Attribute {
    use Attribute;
    use Slate\Metalang\MetalangAttribute;
    use Slate\Utility\Logger;

    #[Attribute(Attribute::TARGET_METHOD)]
    class Benchmark extends MetalangAttribute {
        public const NAME = "Benchmark";
    
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
            else if(\Str::startswith($this->pipe, "log:")) {
                $logger = \Str::afterFirst($this->pipe, ":");
    
                if(!Log::has($logger))
                    throw new \Error(
                        \Str::format(
                            "Unknown logger '{}' specified as a benchmark pipe for {}::{}().",
                            $logger,
                            $this->parent->getDeclaringClass()->getName(),
                            $this->parent->getName()
                        )
                    );
    
                Log::logger($logger)->log(Logger::DEBUG, $message);
            }
            else {
                throw new \Error("Unknown benchmark pipe '".$this->pipe."'");
            }
        }
    }
    
}

?>