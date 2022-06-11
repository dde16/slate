<?php declare(strict_types = 1);

namespace Slate\Neat\Attribute {
    use Attribute;
    use ReflectionMethod;
    use Slate\Metalang\MetalangAttribute;
    use Slate\Neat\InstanceCarry;
    use Slate\Neat\StaticCarry;
    use Slate\Metalang\MetalangDesign;

    #[Attribute(Attribute::TARGET_METHOD)]
    class Carry extends MetalangAttribute {

        protected string $carrying;

        public function __construct(string $class) {
            $this->carrying = $class;

            if(!class_exists($this->carrying))
                throw new \Error(\Str::format(
                    "The Carry Attribute defined for method {}::{} specifies class '{}' that doesn't exist.",
                    $this->parent->getDeclaringClass()->getName(),
                    $this->parent->getName(),
                    $this->carrying
                ));
        }
        
        public function new(string $static): object {
            return (new $this->carrying());
        }
    }
}

?>