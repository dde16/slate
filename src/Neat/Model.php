<?php declare(strict_types = 1);

namespace Slate\Neat {

    use Closure;
    use Generator;
    use Slate\Metalang\MetalangTrackedClass;
    use Slate\Neat\Implementation\TMacroableImplementation;
    use Slate\Neat\Implementation\TCacheAttributeImplementation;
    use Slate\Neat\Implementation\TGetterAttributeImplementation;
    use Slate\Neat\Implementation\TSetterAttributeImplementation;
    use Slate\Neat\Implementation\TBenchmarkAttributeImplementation;
    use Slate\Neat\Implementation\TRetryAttributeImplementation;
    use Slate\Neat\Implementation\TAliasAttributeImplementation;
    use Slate\Neat\Implementation\TCarryAttributeImplementation;
    use Slate\Neat\Implementation\TJsonAttributeImplementation;
    use Slate\Neat\Implementation\TThrottleAttributeImplementation;
    use Slate\Neat\Implementation\TPropertyAttributeImplementation;

    use Slate\Neat\Attribute\Fillable;
    use Slate\Neat\Attribute\Initialiser;

    class Model extends MetalangTrackedClass {
        public const DESIGN  = ModelDesign::class;

        use TMacroableImplementation;
        use TCacheAttributeImplementation;
        use TGetterAttributeImplementation;
        use TSetterAttributeImplementation;
        use TThrottleAttributeImplementation;
        use TBenchmarkAttributeImplementation;
        use TRetryAttributeImplementation;
        use TAliasAttributeImplementation;
        use TCarryAttributeImplementation;
        use TJsonAttributeImplementation;
        use TPropertyAttributeImplementation;

        /**
         * @var string $context
         * Controls the context to which some of the Attributes operate under.
         * This is for eg. if you want to fill an object normally or with an api.
         */
        protected ?string $context = null;


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
                if(static::design()->getAttrInstance(Fillable::class, $propertyName)) {
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
                \Arr::associate($properties, null),
                function($fromKey, $toKey) {
                    if($toKey === null)
                        $toKey = $fromKey;
                    

                    if(\Str::startswith($fromKey, "@")) {
                        $fromKey = \Str::removePrefix($fromKey, "@");

                        return [
                            $this->__getx($fromKey),
                            $toKey instanceof Closure ? $toKey($this) : $this->__getx($toKey)
                        ];
                    }
                    else {
                        $value =  $this->__getx($fromKey);


                        if($toKey instanceof Closure) {
                            $value = $toKey($value);
                            $toKey = $fromKey;
                        }
                        else if(is_array($toKey)) {
                            if(is_object($value) ? \Cls::isSubclassInstanceOf($value, Model::class) : false) {
                                $value = $value->toArray($toKey);
                            }
                            else if(is_string($value) ? class_exists($value) : false) {
                                $value = \Arr::format(\Cls::getConstants($value), $toKey);
                            }
                            
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