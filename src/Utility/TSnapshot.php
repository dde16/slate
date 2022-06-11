<?php declare(strict_types = 1);

namespace Slate\Utility {
    trait TSnapshot {
        protected ?string $objectSnapshot    = null;
        protected array   $propertySnapshots = [];

        public function snap(
            array $properties = [],
            bool $store = false,
            string $algorithm = "crc32",
            bool $binary = false,
            bool $nulls = true,
            bool $suppress = true
        ): ?array {
            $reflection = (new \ReflectionClass(static::class));

            if(\Arr::isEmpty($properties)) {
                $ignore = \Arr::unique(
                    \Arr::merge(
                        \Cls::getConstant(static::class, "SNAPSHOT_IGNORE", []),
                        ["objectSnapshot", "propertySnapshots"]
                    )
                );

                if(\Cls::hasInterface(static::class, ISnapshotExplicit::class)) {
                    $properties = \Arr::map(
                        static::getSnapshotProperties(),
                        function($propertyName) use ($reflection) {
                            return $reflection->getProperty($propertyName);
                        }
                    );
                }
                else {
                    $properties = \Arr::filter(
                        \Arr::map(
                            $reflection->getProperties(),
                            function($property) use($ignore) {
                                $propertyName = $property->getName();
            
                                return
                                    (!$property->isStatic() && !\Arr::contains($ignore, $propertyName))
                                        ? $property
                                        : null;
                            }
                        )
                    );
                }
            }
            else {
                $properties = \Arr::filter(
                    \Arr::map(
                        $properties,
                        function($propertyName) use($reflection) {
                            $property = $reflection->getProperty($propertyName);

                            if(!$property->isStatic())
                                return $property;

                            return null;
                        }
                    )
                );
            }

            $propertySnapshots = [];

            foreach($properties as $property) {
                $propertyName = $property->getName();
                
                if(!$property->isPublic())
                    $property->setAccessible(true);

                $value = $property->isInitialized($this) ? $this->{$propertyName} : null;

                if(!$property->isPublic())
                    $property->setAccessible(false);

                if($value !== null || $nulls === true) {
                    try {
                        $valueSerialised = serialize($value);
                        $valueHashed     = hash($algorithm, $valueSerialised, binary: $binary);

                        $propertySnapshots[$propertyName] = $valueHashed;
                    }
                    catch(\Throwable $t) {
                        if(!$suppress) throw $t;
                    }
                }
            }

            $objectSnapshot = hash($algorithm, serialize($propertySnapshots), binary: $binary);

            if($store) {
                $this->objectSnapshot = $objectSnapshot;
                $this->propertySnapshots = $propertySnapshots;
            }

            return [$objectSnapshot, $propertySnapshots];
        }

        public function hasChanged(): bool {
            return $this->snap(store: false)[0] !== $this->objectSnapshot;
        }

        public function getChanges(array $options = []): array {
            if(@$options["properties"] === null && $this->objectSnapshot !== null)
                $options["properties"] = \Arr::keys($this->propertySnapshots);

            list($currentObjectSnapshot, $currentPropertySnapshots) = $this->snap(...array_merge($options, ["store" => false]));

                
            return $currentObjectSnapshot !== $this->objectSnapshot
                ? \Arr::keys(array_diff_assoc($currentPropertySnapshots, $this->propertySnapshots))
                : [];
        }
    }
}

?>