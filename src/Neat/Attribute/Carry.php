<?php

namespace Slate\Neat\Attribute {
    use Attribute;
    use Slate\Metalang\MetalangAttribute;
    use Slate\Neat\InstanceCarry;
    use Slate\Neat\StaticCarry;
    use Slate\Utility\TMiddleware;

    #[Attribute(Attribute::TARGET_METHOD)]
    class Carry extends MetalangAttribute {
        use TMiddleware;

        protected static array $middleware = [
            "instance.carry" => InstanceCarry::class,
            "static.carry"   => StaticCarry::class,
        ];

        protected static array $using = [
            "instance.carry" => InstanceCarry::class,
            "static.carry"   => StaticCarry::class,
        ];

        public const NAME = "Carry";

        protected string $carrying;

        public function __construct(string $class) {
            $this->carrying = $class;
        }

        public function consume($method): void {
            parent::consume($method);

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