<?php declare(strict_types = 1);

namespace Slate\Structure {

    use ReflectionClass;
    use ReflectionProperty;
    use Slate\Utility\TObjectHelpers;

    trait TStruct {
        use TObjectHelpers;

        public function __construct(array $properties) {
            $reflection = new ReflectionClass(static::class);

            foreach(\Arr::filter($reflection->getProperties(), fn(ReflectionProperty $property): bool => !$property->isStatic()) as $property)
                if(\Arr::hasKey($properties, $property->getName()))
                    $this->{$property->getName()} = $properties[$property->getName()];
        }

        public function __get(string $name): mixed {
            return $this->{$name};
        }

        public static function fromArray(array $properties): static {
            return new static($properties);
        }
    }
}

?>