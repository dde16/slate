<?php declare(strict_types = 1);

namespace Slate\Neat {

    use Generator;
    use Slate\Metalang\MetalangTrackedDesign;
    use Slate\Facade\App;
    use Slate\Mvc\Env;
    use Slate\Neat\Attribute\Column as ColumnAttribute;
    use Slate\Neat\Attribute\OneToAny;
    use Slate\Neat\Attribute\OneToOne as OneToOneAttribute;
    use Slate\Neat\Attribute\PrimaryColumn as PrimaryColumnAttribute;
    use Slate\Sql\SqlColumn;

    class EntityDesign extends ModelDesign
    {
        public static array $mappers = [];
        public static array $mapped  = [];

        /**
         * Used as storage for the primary key of the entity,
         * so if there is a duplicate key it can be detected
         * before sending to the server.
         */
        public array $index   = [];

        protected bool  $queryable   = false;

        public function hasIndex(string|int $index): bool {
            return \Arr::hasKey($this->index, $index);
        }

        public function resolveIndex(string|int $index): int|null {
            return $this->index[$index];
        }

        public function addIndex(string|int $index, int|object $instance): void {
            $this->index[$index] = is_object($instance) ? spl_object_id($instance) : $instance;
        }

        public function discardInstance(object|int $instance): void {
            if (is_object($instance)) {
                $instance = spl_object_id($instance);
            }

            if (
                ($index = \Arr::find($this->index, $instance)) !== -1
            ) {
                unset($this->index[$index]);
            }

            unset($this->instances[$instance]);
        }

        public function __construct(string $class) {
            parent::__construct($class);

            if (\Cls::isSubclassOf($class, Entity::class)) {
                $schema     = \Cls::getConstant($class, "SCHEMA");
                $table      = \Cls::getConstant($class, "TABLE");

                if ($schema !== null && $table !== null) {
                    $this->queryable = true;
                } elseif (!$this->isAbstract()) {
                    throw new \Error(\Str::format(
                        "Non-abstract entity {} doesn't specify the Schema or Table.",
                        $this->getName()
                    ));
                }
            } elseif ($class !== Entity::class) {
                throw new \Error(\Str::format(
                    "Trying to create an Entity design for class '{}' that doesnt descend from an Entity.",
                    $class
                ));
            }

            if (count($this->getPrimaryKeys()) === 0) {
                throw new \Error(\Str::format(
                    "Entity {} must have atleast one column as a primary key.",
                    $this->getName()
                ));
            }
        }

        public static function byReference(string $schema, string $table): array {
            return \Arr::filter(
                static::$designs,
                function ($design) use ($schema, $table) {
                    return (
                        $design->getConstantValue("SCHEMA") === $schema
                        && $design->getConstantValue("TABLE") === $table
                    );
                }
            );
        }

        public function getColumnRelationships(string $localProperty): array {
            $relationships = [];

            /** @var OneToAny $oneToAny */
            foreach ($this->getAttrInstances(OneToAny::class, true) as $oneToAny) {
                if ($oneToAny->localProperty === $localProperty) {
                    $relationships[] = $oneToAny;
                }
            }

            return $relationships;
        }

        public function isQueryable(): bool {
            return $this->queryable;
        }

        public function getIncrementalColumn(): ColumnAttribute|null {
            return \Arr::first(
                $this->getAttrInstances(ColumnAttribute::class),
                fn (ColumnAttribute $attribute): bool => $attribute instanceof PrimaryColumnAttribute
            );
        }

        /**
         * Get all the primary keys for this
         *
         * @return array
         */
        public function getPrimaryKeys(): array {
            return \Arr::filter($this->getAttrInstances(ColumnAttribute::class), fn (ColumnAttribute $attribute): bool => $attribute instanceof PrimaryColumnAttribute);
        }

        public function isPrimaryKey(string $propertyName): bool {
            return $this->getAttrInstance(PrimaryColumnAttribute::class, $propertyName) !== null;
        }

        public function hasCompositePrimaryKey(): bool {
            return count($this->getPrimaryKeys()) > 1;
        }

        public function getPrimaryKey(): ColumnAttribute|null {
            return \Arr::first(
                $this->getAttrInstances(ColumnAttribute::class),
                fn (ColumnAttribute $attribute): bool => $attribute instanceof PrimaryColumnAttribute
            );
        }

        public function getRelationship(array|string $path): ?OneToAny {
            if(is_string($path))
                $path = \Str::split($path, ".");
            
            /** @var OneToAny */
            $relationship = $this->getAttrInstance(OneToAny::class, $path[0]);

            if($relationship === null)
                return null;

            if(count($path) === 1)
                return $relationship;

            return $relationship->getForeignDesign()->getRelationship(\Arr::slice($path, 1));
        }

        public function getColumns(): array {
            return $this->getAttrInstances(ColumnAttribute::class);
        }

        /**
         * Get a column property by its php property name.
         *
         * @param string $name
         *
         * @return ColumnAttribute|null
         */
        public function getColumnProperty(string $propertyName): ColumnAttribute|null {
            return \Arr::first(
                $this->getAttrInstances(ColumnAttribute::class),
                fn ($attribute) => $attribute->parent->getName() === $propertyName
            );
        }

        public function getColumn(string $columnName): ColumnAttribute|null
        {
            return \Arr::first(
                $this->getAttrInstances(ColumnAttribute::class),
                fn ($attribute) => $attribute->getColumnName() === $columnName
            );
        }
    }
}
