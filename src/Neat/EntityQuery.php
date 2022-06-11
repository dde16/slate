<?php declare(strict_types = 1);

namespace Slate\Neat {

    use Closure;
    use Generator;
    use Slate\Data\Iterator\ArrayRecursiveIterator;
    use Slate\Facade\DB;
    use Slate\Facade\Sql;
    use Slate\Neat\Attribute\Column as ColumnAttribute;
    use Slate\Neat\Attribute\OneToAny;
    use Slate\Neat\Attribute\OneToOne;
    use Slate\Neat\Entity;
    use Slate\Neat\EntityDesign;
    use Slate\Neat\Interface\IModelFactory;
    use Slate\Sql\Clause\TSqlFromClause;
    use Slate\Sql\Clause\TSqlJoinClause;
    use Slate\Sql\Clause\TSqlLimitClause;
    use Slate\Sql\Clause\TSqlOrderByClause;
    use Slate\Sql\Clause\TSqlWhereInlineClause;
    use Slate\Sql\Expression\TSqlColumnsExpression;
    use Slate\Sql\SqlModifier;
    use Slate\Sql\SqlStatement;
    use Slate\Sql\Statement\TSqlSelectStatementGet;
    use RuntimeException;
    use Slate\Sql\Statement\TSqlSelectStatementExists;

    class EntityQuery extends SqlStatement {
        public const MODIFIERS =
            SqlModifier::HIGH_PRIORITY
            | SqlModifier::NO_CACHE
            | SqlModifier::BIG_RESULT
            | SqlModifier::SMALL_RESULT
            | SqlModifier::BUFFER_RESULT
        ;
    
        use TSqlColumnsExpression;
        use TSqlFromClause;
        use TSqlWhereInlineClause {
            TSqlWhereInlineClause::deriveCondition as _deriveCondition;
        }
        use TSqlOrderByClause;
        use TSqlLimitClause;
    
        use TSqlJoinClause;

        use TSqlSelectStatementExists;
    
        use TSqlSelectStatementGet {
            TSqlSelectStatementGet::get as _get;
            TSqlSelectStatementGet::all as _all;
        }
    
        /**
         * The entity the query is for.
         *
         * @var Entity|string
         */
        public string $entity;
    
        /**
         * Child queries.
         *
         * @var array
         */
        public array $relationships;
    
        /**
         * Must have atleast one model for the relationship.
         *
         * @var array
         */
        public array $havings;
    
        /**
         * Must have no models for the relationship.
         *
         * @var array
         */
        public array $havingNots;
    
        /**
         * Left joined relationships.
         * 
         * @var array
         */
        public array $withs;
    
        /**
         * Inner joined relationships
         * 
         * @var array
         */
        public array $withsHaving;
    
        /**
         * Custom counts injected into the query
         *
         * @var array
         */
        public array $withCounts;
    
        /**
         * Raw columns.
         * 
         * @var string[]
         */
        public array $raw;
    
        /**
         * Scopes to be ran on the query, featured on the model.
         *
         * @var array
         */
        public array $scopes;
        
        /**
         * Stores the recursive relationships.
         * 
         * @var array
         */
        public array $recursives;

        /**
         * The attribute to which this relationship will 'run along'.
         *
         * @var OneToAny
         */
        public ?OneToAny $along = null;
    
        public function __construct(string $entity, array $plan = []) {
            parent::__construct($entity::conn());
            $this->registerWheres();
    
            $this->entity        = $entity;
            $this->relationships = [];
            $this->havings       = [];
            $this->havingNots    = [];
            $this->withs         = [];
            $this->withsHaving   = [];
            $this->withCounts    = [];
            $this->recursives    = [];
            $this->from($entity::table()->fullname());

            $this->plan($plan);
        }
    
        public function __clone() {
            $wheres = $this->wheres->children;
            $this->registerWheres();
            $this->wheres->children = $wheres;
        }
    
        /**
         * Set the along column relationship.
         *
         * @param OneToAny $along
         *
         * @return void
         */
        public function along(OneToAny $along): void {
            $this->along = $along;
    
            if($along instanceof OneToOne)
                $this->limit(1);
        }
    
        public function isRelationImmediate(string|array $relationship) {
            if(is_string($relationship))
                return \Str::count($relationship, ".") === 0;
    
            return count($relationship) === 1;
        }
    
        public function nest(string $property, string $function, string $relationshipName, ?Closure $filter, array $exclusives = []) {
            if($this->isRelationImmediate($relationshipName)) {
                $this->{$property}[$relationshipName] = $filter;
            }

            $relationshipName = \Str::split($relationshipName, ".");

            $relationship = $this->relationship($relationshipName[0], assert: true);
    
            if(!$this->isRelationImmediate($relationshipName)) {
                $relationship->{$function}(\Arr::join(\Arr::slice($relationshipName, 1), "."), $filter);
            }
            else if($filter) {
                $filter($relationship);
            }
    
            return $this;
        }
        
        public function has(string $relationship, ?Closure $filter = null): static {
            return $this->nest("havings", "has", $relationship, $filter, ["havingNots", "withs", "withsHaving", "withCounts"]);
        }
    
        public function hasnt(string $relationship, ?Closure $filter = null) {
            return $this->nest("havingNots", "hasnt", $relationship, $filter);
        }
        
        public function with(string $relationship, ?Closure $filter = null) {
            return $this->nest("withs", "with", $relationship, $filter);
        }
    
        public function withHas(string $relationship, ?Closure $filter = null) {
            return $this->nest("withsHaving", "withHas", $relationship, $filter);
        }
    
        /**
         * Get all the relationships along a path.
         *
         * @return EntityQuery[]
         */
        public function relationships(array|string $path): array {
            if(is_string($path)) {
                $path = \Str::split($path, ".");
            }
    
            $relationship = $this;
            $relationships = [];
    
            while($relationship !== null && count($path) > 0) {
                $relationship = $relationship->relationship($path[0]);
                $relationships[] = $relationship;
    
                $path = \Arr::slice($path, 1);
            }
    
            return $relationships;
        }
    
        public function deriveRelationship(string $name, bool $assert = false): ?self {
            /** @var OneToAny $relationshipAttribute */
            if(($relationshipAttribute = $this->entity::design()->getAttrInstance(OneToAny::class, $name)) === null) {
                if($assert)
                    throw new RuntimeException("Unknown relationship {$this->entity}->$name");
    
                return null;
            }
    
            /** @var EntityDesign */
            $class = $relationshipAttribute->getForeignDesign();
            $relationship = $class->invokeStaticMethod("query");
            $relationship->along($relationshipAttribute);
    
            return $relationship;
        }
    
        public function relationship(string|array $path, bool $assert = false): ?EntityQuery {
            if(is_string($path)) 
                $path = \Str::split($path, ".");
    
            if(!\Arr::hasKey($this->relationships, $path[0])) {
                if(($relationship = $this->deriveRelationship($path[0], $assert)) !== null) {
                    $this->relationships[$path[0]] = $relationship;
                }
            }
    
            $relationship = $this->relationships[$path[0]];
    
            if(count($path) > 1)
                $relationship = $relationship->relationship(\Arr::slice($path, 1));
            
            return $relationship;
        }
        
        public function buildSql(): ?array {
            return [
                "SELECT",
                ...$this->buildModifiers([
                    SqlModifier::HIGH_PRIORITY,
                    SqlModifier::BIG_RESULT,
                    SqlModifier::SMALL_RESULT,
                    SqlModifier::BUFFER_RESULT,
                    SqlModifier::NO_CACHE,
                ]),
                $this->buildColumnsExpression(),
                $this->buildFromClause(),
                $this->buildJoinClause(),
                $this->buildWhereClause(),
                $this->buildOrderByClause(),
                $this->buildLimitClause()
            ];
        }
    
        public function toCountSql(): string {
            $query = (clone $this)->column("1");
            $query->loadRelationshipChecks();
            $query->limit(1);
    
            return $this->conn->select()->column(Sql::exists($query))->toSql();
        }
    
        public function hasCountableRelationships(): bool {
            if(count($this->relationships) > 0 ? \Arr::any($this->relationships, fn(EntityQuery $relationship): bool => $relationship->hasCountableRelationships()) : false)
                return true;
    
            return
                !\Arr::isEmpty($this->havings)
                || !\Arr::isEmpty($this->withsHaving)
                || !\Arr::isEmpty($this->havingNots)
            ;
        }
    
        /**
         * Load the count relationship checks into this query.
         *
         * @return void
         */
        public function loadRelationshipChecks(): void {
            foreach($this->relationships as $relationshipName => $relationship) {
                if(!\Arr::hasKey($this->withs, $relationshipName)) {
                    if($relationship->hasCountableRelationships() !== false) {
                        $relationship = clone $relationship;
                        $relationship->where(...$this->toRelationshipWhere($relationship));
                        $sql = $relationship->toCountSql();
                        $this->where(DB::raw(\Str::wrapc($sql, "()")));
                    }
                }
            }
    
            foreach([...$this->havings, ...$this->withsHaving] as $relationshipName => $relationshipFilter) {
                $relationship = $this->loadRelationshipCheck($relationshipName, $relationshipFilter instanceof Closure ? $relationshipFilter : null);
                $sql = $relationship->toCountSql();
    
                if($sql !== null) {
                    $this->where(DB::raw(\Str::wrapc($sql, "()")));
                }
            }
    
            foreach($this->havingNots as $relationshipName => $relationshipFilter) {
                $relationship = $this->loadRelationshipCheck($relationshipName, $relationshipFilter);
                $sql = $relationship->limit(1)->toCountSql();
    
                if($sql !== null) {
                    $this->where(DB::raw(\Str::wrapc($sql, "()")), "=", 0);
                }
            }
        }
    
        public function toRootCountSql(): static {
            $query = clone $this;
    
            if($this->along === null)
                $query->column($this->entity::table()->fullname().".*");
    
            $query->loadRelationshipChecks();
    
            return $query;
        }
    
        public function loadRelationshipCheck(string $relationshipName, ?Closure $relationshipFilter) {
            $relationship = $this->deriveRelationship($relationshipName);
    
            $relationship->where(...$this->toRelationshipWhere($relationship));
    
            if($relationshipFilter)
                $relationship->where($relationshipFilter);
    
            return $relationship;
        }
    
        public function toRelationshipWhere(self $relationship): array {
            $localTable = $this->entity::table();
            $localColumn = $this->entity::design()->getColumnProperty($relationship->along->getLocalProperty())->getColumnName();
    
            $foreignTable = $relationship->along->getForeignTable();
            $foreignColumn = $relationship->along->getForeignColumn();
    
            return [DB::raw($localTable->ref($localColumn)), "=", DB::raw($foreignTable->ref($foreignColumn))];
        }
    
        /**
         * @param Entity[] $parentModels
         * @return Entity[]
         */
        public function get(array $parentModels = null, bool $reset = false) {
            $root = $parentModels === null || $reset;
    
            if($root) {
                $query = $this->toRootCountSql();
            }
            else  {
                if($parentModels === null)
                    throw new RuntimeException();
                    
                if(count($parentModels) === 0)
                    return [];
    
                $query = $this->toRootCountSql();
            }
    
            if($parentModels !== null ? count($parentModels) > 0 : false) {
                $parentPrimaryKeys = \Arr::map(
                    $parentModels,
                    fn(Entity $parentModel) => $parentModel->{$this->along->getLocalProperty()}
                );
    
                $query->where($this->along->getForeignColumn(), "IN", $parentPrimaryKeys);
            }
    
            $limit = $this->limit;
            $offset = $this->offset;
    
            if(($limit !== null || $offset !== null) && $this->along) {
                $query->limit = null;
                $query->offset = null;
    
                $_query = $query;
    
                $rowNumber = Sql::winfn("ROW_NUMBER")->partitionBy($this->along->getForeignColumn());
                $rowNumber->orderBy = $query->orderBy;
                $rowNumber->orderDirection = $query->orderDirection;
    
                $query->orderBy = [];
                $query->orderDirection = null;
    
                $_query
                    ->column("*")
                    ->column(
                        $rowNumber,
                        "`\$RowNumber`"
                    )
                ;
    
                $query = 
                    $this->conn()
                        ->select()
                        ->from($_query, "_");
    
                if($limit !== null && $offset === null) {
                    $query->where("`_`.`\$RowNumber`", "<=", $limit);
                }
                else if($limit === null && $offset !== null) {
                    $query->where("`_`.`\$RowNumber`", ">", $offset);
                }
                else if($limit !== null && $offset !== null) {
                    $query->where("`_`.`\$RowNumber`", ">", $offset);
                    $query->where("`_`.`\$RowNumber`", "<=", $offset + $limit);
                }
    
                // debug($this->along->parent->getDeclaringClass()->getName());
                // debug($this->along->parent->getName());
                // debug((new SqlFormatter())->format($query->toSql()));
                $childModels = $query->all();
            }
            else {
                // debug((new SqlFormatter())->format($query->toSql()));
                $childModels = iterator_to_array($query->_get());
            }
    
            if(count($childModels) === 0)
                return $parentModels ?? [];
    
            $entity = $this->entity;
            $design = $this->entity::design();
    
            foreach($childModels as $index => $row) {
                $row = \Arr::except($row, "\$RowNumber");
    
                if(\Cls::hasInterface($entity, IModelFactory::class)) {
                    $model = $entity::factory(
                        \Obj::fromArray(
                            \Arr::rearrange(
                                $row,
                                \Arr::mapAssoc(
                                    \Arr::values($design->getAttrInstances(ColumnAttribute::class)),
                                    function(int $index, ColumnAttribute $column): array {
                                        return [$column->getColumnName(), $column->parent->getName()];
                                    }
                                )
                            )
                        )
                    );
                }
                else {
                    $model = new $entity;
                }
    
                $model->fromSqlRow($row);
                $childModels[$index] = $model;
            }
    
            foreach(\Arr::keys($this->withsHaving) as $relationshipName) {
                $relationship = $this->relationships[$relationshipName];
                $childModels = $relationship->get($childModels);
            }
    
            foreach(\Arr::keys($this->withs) as $relationshipName) {
                $relationship = $this->relationships[$relationshipName];
                $childModels = $relationship->get($childModels, true);
            }
    
            if($parentModels !== null) {
                foreach($parentModels as $parentModel) {
                    $parentModelChildren = \Arr::filter(
                        $childModels,
                        function($childModel) use($parentModel) {
                            return  $parentModel->{$this->along->getLocalProperty()} === $childModel->{$this->along->getForeignProperty()};
                        }
                    );
    
                    $parentModel->{$this->along->parent->getName()} = (
                        $this->along instanceof OneToOne
                            ? \Arr::first($parentModelChildren)
                            : \Arr::values($parentModelChildren)
                    );
                }
            }
    
            return $parentModels ?? $childModels;
        }
    
        public function first() {
            $query = clone $this;
            $query->limit(1);
    
            return $query->get()[0];
        }
    
        public function _page(int $size, int $number): static {
            $query = clone $this;
    
            $query->limit(
                $size+1,
                ($size * $number) + intval($number > 0)
            );
    
            return $query;
        }
    
        public function page(int $size, int $number): array {
            $query = $this->_page($size, $number);
    
            $rows = $query->get();
    
            list($primaryChunk, $secondaryChunk) = \Arr::padRight(\Arr::chunk($rows, $size), [], 2);
    
            $hasNext = !\Arr::isEmpty($secondaryChunk);
    
            return [$primaryChunk, $hasNext, $query];
        }
    
        public function chunk(int $size, bool $aggregate = false): Generator {
            $page = 0;
    
            do {
                [$models, $hasNextPage] = $this->page($size, $page);
    
                if(count($models) > 0) {
                    if($aggregate) {
                        foreach($models as $model) {
                            yield $model;
                        }
                    }
                    else {
                        yield $models;
                    }
                }
    
                $page++;
            } while($hasNextPage);
        }
    
        public function take(int $amount): array {
            $query = clone $this;
            $query->limit($amount);
    
            return $query->get();
        }
    
    
        public function option(string $name, mixed $value): void {
            switch ($name) {
                case "orderDirection":
                    $this->{$name} = $value;
                    break;
    
                case "callback":
                    $value($this);
                    break;
                
                case "where":
                case "limit":
                case "offset":
                case "orderBy":
                case "orderByAsc":
                case "orderByDesc":
                    $this->{$name}($value);
                    break;
    
                case "scope":
                case "scopes":
                    throw new \Error("Scopes are deprecated, please use QueryBuilders for this purpose.");
                    break;
                
                case "flag":
                    throw new \Error("Flags are deprecated, please use '?' at the end of your relationship to denote its join.");
                    break;
    
                default:
                    break;
            }
        }
    
    
        public function plan(array $plan): static {
            $plan = \Arr::associate($plan, null, true);
    
            \Arr::mapRecursive(
                $plan,
                function(string|int $key, mixed $anon): array {
                    if((is_object($anon)  && !\Str::startswith($key, "@")) ? $anon instanceof Closure : false) {
                        $anon = ["@callback" => $anon];
                    }
    
                    // if(\Str::endswith($key, "?")) {
                    //     $flag = substr($key, -1);
    
                    //     if(!is_array($anon))
                    //         $anon = [];
    
                    //     $anon["@flag"] = $flag;
                    //     $key = substr($key, 0, -1);
                    // }
            
                    return [$key, $anon];
                }
            );
    
            $iterator = new ArrayRecursiveIterator($plan);
            
            foreach($iterator as $path => $value) {
                $path = \Str::split($path, ".");
                $key = \Arr::last($path);
            
                $option = \Str::startswith($key, "@");
    
                if(($option || !is_array($value)) && \Arr::all(\Arr::slice($path, 0, -1), fn($segment) => !\Str::startswith($segment, "@"))) {
                    $last = $this;
    
                    foreach($path as $index => $segment) {
                        $end = ($index === (count($path) - 1));
    
                        if(\Str::startswith($segment, "@") && $end) {
                            $last->option(\Str::removePrefix($segment, "@"), $value);
                        }
                        else {
                            if(!\Arr::hasKey($last->relationships, $segment)) {
                                if(\Str::endswith($segment, "?")) {
                                    $segment = \Str::removeSuffix($segment, "?");
                                    $last->with($segment);
                                }
                                else {
                                    $last->withHas($segment);
                                }
                            }
    
                            $last = $last->relationship($segment, true);
                        }
                    }
                }
            }

            return $this;
        }

        public function __get(string $name): mixed {
            $design = $this->entity::design();

            if($column = $design->getColumnProperty($name))
                return $column->getColumnName();

            return null;
        }
    }
}

?>