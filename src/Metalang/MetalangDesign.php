<?php declare(strict_types = 1);

namespace Slate\Metalang {

    use Generator;
    use ReflectionClass;
    use ReflectionException;
    use ReflectionMethod;
    use Slate\Metalang\Attribute\HookCall;

    class MetalangDesign extends ReflectionClass {
        public static array $designs = [];

        // public array $customAttributes = [];
        public array $customAttributeInstances = [];

        public array $implementorCache = [];
    
        protected function __construct(string $targetClass) {
            parent::__construct($targetClass);
    
            if(!\Cls::isSubclassOf($targetClass, MetalangClass::class)) {
                throw new \Error("Class Design cannot accept classes that are not derived from a " . \Str::afterLast(MetalangClass::class, "\\"));
            }
    
            foreach([[$this], $this->getConstants(), $this->getProperties(), $this->getMethods()] as $constructs) {
                $this->customAttributeInstances = \Arr::merge(
                    $this->customAttributeInstances,
                    $this->instaniateAttributesOf($constructs)
                );
            }

            $this->getHookCache(HookCall::class);
            $this->getHookCache(HookCallStatic::class);
            $this->getHookCache(HookGet::class);
            $this->getHookCache(HookSet::class);
        }

        public function bootstrapAttributes(): void {
            foreach($this->customAttributeInstances as $customAttributeClass => $customAttributeInstances) {
                foreach($customAttributeInstances as $customAttributeInstance) {
                    if(!$customAttributeInstance->isBootstrapped()) {
                        $customAttributeInstance->bootstrap($this);
                    }
                }
            }
        }

        public function getHookCache(string $implementorClass): array {
            $implementorCache = &$this->implementorCache[$implementorClass];

            if($implementorCache === null) {
                $implementorCache = $this->generateHookCache($implementorClass);
            }

            return $implementorCache;
        }

        public function generateHookCache(string $implementorClass): array {
            $allImplementors = $this->getAttrInstances(
                $implementorClass
            );
            
            $baseImplementors = [];
            
            foreach($allImplementors as $lastImplementor) {
                $lastImplementorReferenced = false;
            
                foreach($allImplementors as $nextImplementor) {
                    if($lastImplementor->getKeys() !== $nextImplementor->getKeys()) {
                        if(\Arr::contains($nextImplementor->getNextKeys(), $lastImplementor->getNextKeys())) {
                            $lastImplementorReferenced = true;
                            break;
                        }
                    }
                }
            
                if(!$lastImplementorReferenced)
                    $baseImplementors[] = $lastImplementor;
            }

            return $baseImplementors;
        }
    
        public function instaniateAttributesOf(array $constructs): array { 
            $customAttributeInstances = [];
    
            foreach($constructs as $constructObject) {
                foreach($constructObject->getAttributes() as $constructAttribute) {
                    list(
                        $constructAttributeType,
                        $constructAttributeInstance
                    ) = $constructAttribute->newInstance();
    
                    $constructAttributeInstanceKeys = $constructAttributeInstance->getKeys();
    
                    if(is_scalar($constructAttributeInstanceKeys)) {
                        $constructAttributeInstanceKeys = [$constructAttributeInstanceKeys];
                    }
    
                    if(!\Arr::hasKey($customAttributeInstances, $constructAttributeType))
                        $customAttributeInstances[$constructAttributeType] = [];
    
                    foreach($constructAttributeInstanceKeys as $constructAttributeInstanceKey) {
                        if(\Arr::hasKey($customAttributeInstances[$constructAttributeType], $constructAttributeInstanceKey))
                            throw new \Error(\Str::format(
                                "Attribute instance for {} already has key taken '{}'.",
                                $constructAttributeType,
                                $constructAttributeInstanceKey
                            ));
                        
    
                        $customAttributeInstances[$constructAttributeType][$constructAttributeInstanceKey] = $constructAttributeInstance;
                    }
    
                }
            }
    
            return $customAttributeInstances;
        }

        public function hasAttrInstance(string|array $classes, string $key, bool $subclasses = false): bool {
            return ($this->getAttrInstance($classes, $key, $subclasses) !== null);
        }

        public function invokeStaticMethod(string $method, array $arguments = []): mixed {
            if(!$this->hasMethod($method, ReflectionMethod::IS_STATIC))
                throw new ReflectionException(\Str::format(
                    "Trying to invoke static method {}::{}() which doesn't exist.",
                    $this->getName(),
                    $method
                ));

            return $this->getMethod($method)->invokeArgs(null, $arguments);
        }
    
        public function invokeMethod(object $object, string $method = "__invoke", array $arguments = []): mixed {
            if(!$this->hasMethod($method, ReflectionMethod::IS_STATIC))
                throw new ReflectionException("");

            return $this->getMethod($method)->invokeArgs($object, $arguments);
        }
    
        public function getAttrInstance(string|array $classes, string|int $key, bool $subclasses = true): MetalangAttribute|null {
            $attribute = null;

            if(is_string($classes)) $classes = [$classes];

            foreach($this->customAttributeInstances as $customAttributeClass => $customAttributeArray) {
                if(\Arr::hasKey($customAttributeArray, $key) ?
                    (($_attribute = @$this->customAttributeInstances[$customAttributeClass][$key]) !== null ? 
                        \Cls::{$subclasses ? 'isSubclassInstanceOf' : 'isInstanceOf'}(
                            $_attribute,
                            $classes
                        )
                        : false
                    )
                    : false
                ) {
                    $attribute = $_attribute;
                    break;
                }
            }

            return $attribute ?:
                ((($parentClass = $this->getParentClass()))
                    ? $parentClass->getAttrInstance($classes, $key, subclasses: $subclasses)
                    : null
                );
        }

        public function getParentClass(bool $metalang = true): static|bool {
            return (($parentClass = parent::getParentClass()) !== false)
                ? ($parentClass->isSubclassOf($this->getName()::DESIGN)
                    ? static::of($parentClass->getName())
                    : false
                )
                : false;
        }
    
        public function getAttrInstances(string $class = null, bool $subclasses = true): array {
            return array_merge(
                (($subclasses)
                    ? array_merge(
                        ...\Arr::values(\Arr::filter(
                            $this->customAttributeInstances,
                            function($attributeArray, $attributeClass) use($class) {
                                
                                return \Cls::isSubclassInstanceOf($attributeClass, $class);
                            },
                            \Arr::FILTER_BOTH
                        ))
                    ) : []
                ),
                $class ? (@$this->customAttributeInstances[$class] ?: []) : $this->customAttributeInstances,
                (($parentClass = $this->getParentClass()))
                    ? ($parentClass->getAttrInstances($class))
                    : []
            );
        }
    
        public function hasMethod(string $name, int $flags = 0): bool {
            return parent::hasMethod($name)
                ? ($flags !== 0 ? \Integer::hasBits(
                    $this->getMethod($name)->getModifiers(), $flags) : true)
                : false;
        }
    
        public function hasProperty(string $name, int $flags = 0): bool {
            return parent::hasProperty($name)
                ? ($flags !== 0 ? \Integer::hasBits(
                    $this->getProperty($name)->getModifiers(), $flags) : true)
                : false;
        }
    
        public static function &of(string $class): object {
            $design = &self::$designs[$class];
    
            if($design === null) {
                $design = new (\Cls::getConstant($class, "DESIGN") ?: static::class)($class);
                $design->bootstrapAttributes();
            }

    
            return $design;
        }
    
        public function getConstantValue(string $name, mixed $fallback = null): mixed {
            return parent::getConstant($name) ?: null;
        }
    
        public function getConstantValues(int|null $filter = null): array {
            return parent::getConstants($filter);
        }
    
        public function getConstant(string $name): MetalangClassConstructAttributable|null {
            return $this->hasConstant($name)
                ? (new MetalangClassConstructAttributable($this, parent::getReflectionConstant($name)))
                : null;
        }
    
        public function getConstants(int|null $filter = null): array {
            return \Arr::map(
                parent::getReflectionConstants($filter),
                function($constant) {
                    return(new MetalangClassConstructAttributable($this, $constant));
                }
            );
        }
    
        public function getMethod(string $name): MetalangClassConstructAttributable {
            return(new MetalangMethod($this, parent::getMethod($name)));
        }
    
        public function getMethods(int|null $filter = null): array {
            return \Arr::map(
                parent::getMethods($filter),
                function($method) {
                    return(new MetalangMethod($this, $method));
                }
            );
        }
    
        public function getProperty(string $name): MetalangClassConstructAttributable {
            return(new MetalangClassConstructAttributable($this, parent::getProperty($name)));
        }
    
        public function getProperties(int|null $filter = null): array {
            return \Arr::map(
                parent::getProperties($filter),
                function($property) {
                    return(new MetalangClassConstructAttributable($this, $property));
                }
            );
        }
    }
}

?>