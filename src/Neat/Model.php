<?php

namespace Slate\Neat {

    use Closure;
    use Generator;
    use Slate\Metalang\MetalangTrackedClass;

    use Slate\Neat\Implementation\TCacheAttributeImplementation;
    use Slate\Neat\Implementation\TGetterAttributeImplementation;
    use Slate\Neat\Implementation\TSetterAttributeImplementation;
    use Slate\Neat\Implementation\TBenchmarkAttributeImplementation;
    use Slate\Neat\Implementation\TRetryAttributeImplementation;
    use Slate\Neat\Implementation\TAliasAttributeImplementation;
    use Slate\Neat\Implementation\TCarryAttributeImplementation;
    use Slate\Neat\Implementation\TJsonAttributeImplementation;
    use Slate\Neat\Implementation\TThrottleAttributeImplementation;

    use Slate\Neat\Attribute\Fillable;
    use Slate\Neat\Attribute\Initialiser;

    class Model extends MetalangTrackedClass {
        use TCacheAttributeImplementation;
        use TGetterAttributeImplementation;
        use TSetterAttributeImplementation;
        use TThrottleAttributeImplementation;
        use TBenchmarkAttributeImplementation;
        use TRetryAttributeImplementation;
        use TAliasAttributeImplementation;
        use TCarryAttributeImplementation;
        use TJsonAttributeImplementation;

        public function __construct(array $array = []) {
            parent::__construct();

            $design = static::design();

            foreach($design->getAttrInstances(Initialiser::class) as $initialiser) {
                $propertyName = $initialiser->getProperty();

                if($design->hasProperty($propertyName)) {
                    $this->{$propertyName} = static::{$initialiser->parent->getName()}();
                }
            }

            $this->fromArray($array);
        }

        public function fromArray(array $properties): void {
            foreach($properties as $propertyName => $propertyValue) {
                if(static::design()->getAttrInstance(Fillable::class, $propertyName, subclasses: true)) {
                    $this->__set($propertyName, $propertyValue);
                }
            }
        }

        private function __getx(string $name): mixed {
            if(static::design()->hasProperty($name) ? ($property = static::design()->getProperty($name)) !== null : false) {
                $value = null;

                $property->setAccessible(true);

                if($property->isInitialized($this)) {
                    $value = $this->{$name};
                }

                $property->setAccessible(false);
            }
            else {
                $value = $this->__get($name);
            }

            return $value;
        }

        public function toArray(array $properties): array {
            return \Arr::mapAssoc(
                \Arr::associate($properties, null, function(mixed $collision): Closure|string|null {
                    return (is_array($collision) || is_string($collision) || ($collision instanceof Closure)) ? $collision : null;
                }),
                function($fromKey, $toKey) {
                    if($toKey === null)
                        $toKey = $fromKey;
                    
                    if(\Str::startswith($fromKey, "@")) {
                        $fromKey = \Str::removePrefix($fromKey, "@");

                        $toValue = $this->__getx($toKey);

                        return [
                            $this->__getx($fromKey),
                            $toKey instanceof Closure ? $toKey($toValue) : $toValue
                        ];
                    }
                    else {
                        $value =  $this->__getx($fromKey);

                        if($toKey instanceof Closure) {
                            $value = $toKey($value);
                            $toKey = $fromKey;
                        }
                        else if(is_object($value) ? \Cls::isSubclassInstanceOf($value, Model::class) : false) {
                            $value = $value->toArray($toKey);
                            $toKey = $fromKey;
                        }

                        return [$toKey, $value];
                    }
                }
            );
        }
    }
}

?>