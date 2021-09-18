<?php

namespace Slate\Neat {
    use Slate\Metalang\MetalangTrackedDesign;
    use Slate\Mvc\App;
    use Slate\Mvc\Env;
    use Slate\Neat\Attribute\Column as ColumnAttribute;
    use Slate\Neat\Attribute\OneToOne as OneToOneAttribute;
    use Slate\Sql\SqlColumn;

class EntityDesign extends MetalangTrackedDesign {
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
            if(is_object($instance))
                $instance = spl_object_id($instance);

            if(
                ($index = \Arr::find($this->index, $instance)) !== -1
            ) {
                unset($this->index[$index]);
            }

            unset($this->instances[$instance]);
        }

        public function __construct(string $class) {
            parent::__construct($class);

            if(\Cls::isSubclassOf($class, Entity::class)) {
                $schema     = \Cls::getConstant($class, "SCHEMA");
                $table      = \Cls::getConstant($class, "TABLE");

                if($schema !== null && $table !== null) {
                    $this->queryable = true;
                    $conn = App::conn(\Cls::getConstant($class, "CONN"));

                    $repo = App::repo(Env::get("orm.cache.repository", [ "important" => true ]));

                    $ref = $class::ref()->toString();

                    if($repo->has($ref)) {
                        $columns = $repo->pull($ref);
                    }
                    else {
                        $repo->put($ref, ($columns = $conn->schematic($schema, $table)));
                    }

                    $columns = \Arr::map(
                        $columns,
                        function($column) use($conn) {
                            $inst = new SqlColumn($conn::PREFIX);
                            $inst->fromArray($column);
        
                            return $inst;
                        }
                    );


                    foreach($this->getAttrInstances(ColumnAttribute::class) as $key => $attribute) {
                        $columnName = $attribute->getColumnName();

                        if(\Arr::hasKey($columns, $columnName)) {
                            $attribute->setSqlColumn($columns[$columnName]);
                        }
                        else {
                            throw new \Error(\Str::format("Unknown column {}::\${}.", $this->getName(), $attribute->parent->getName()));
                        }
                    }
                }
                else if(!$this->isAbstract()) {
                    throw new \Error(\Str::format(
                        "Non-abstract entity {} doesn't specify the Schema or Table.",
                        $this->getName()
                    ));
                }
            }
            else if($class !== Entity::class) {
                throw new \Error(\Str::format(
                    "Trying to create an Entity design for class '{}' that doesnt descend from an Entity.",
                    $class
                ));
            }

            if($this->getPrimaryKey() === null)
                throw new \Error(\Str::format(
                    "Entity {} doesn't have a primary key.",
                    $this->getName()
                ));
        }

        public static function byReference(string $schema, string $table): array {
            return \Arr::filter(
                static::$designs,
                function($design) use($schema, $table) {
                    return (
                        $design->getConstantValue("SCHEMA") === $schema 
                        && $design->getConstantValue("TABLE") === $table
                    );
                }
            );
        }

        public function isQueryable(): bool {
            return $this->queryable;
        }

        public function getPrimaryKey(): ColumnAttribute|null {
            return \Arr::first(
                $this->getAttrInstances(ColumnAttribute::class),
                function($attribute) {
                    return $attribute->isPrimaryKey();
                }
            );
        }

        public function getColumns(): array {
            return $this->getAttrInstances(ColumnAttribute::class, subclasses: true);
        }

        public function getColumnProperty(string $name): ColumnAttribute|null {
            return \Arr::first(
                $this->getAttrInstances(ColumnAttribute::class),
                fn($attribute) => $attribute->parent->getName() === $name
            );
        }

        public function getColumn(string $name): ColumnAttribute|null {
            return \Arr::first(
                $this->getAttrInstances(ColumnAttribute::class),
                fn($attribute) => $attribute->getColumnName() === $name
            );
        }
    }
}

?>