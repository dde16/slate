<?php

namespace Slate\Neat {
    use Slate\Data\Graph;
    use Slate\Data\IStringForwardConvertable;
    use Slate\Facade\DB;
    use Slate\Mvc\App;
    use Slate\Neat\Attribute\Column;
    use Slate\Neat\Attribute\OneToAny;
    use Slate\Neat\Attribute\OneToMany;
    use Slate\Neat\Attribute\OneToOne;
    use Slate\Neat\Attribute\Scope;
    use Slate\Neat\Entity;
    use Slate\Sql\Clause\TSqlWhereClause;
    use Slate\Sql\SqlConnection;
    use Slate\Sql\Statement\SqlSelectStatement;
    use Generator;
    use Iterator;
    use Closure;
    use Slate\Data\TStringNativeForwardConvertable;
    use Throwable;

class EntityQuery implements IStringForwardConvertable {
        use TStringNativeForwardConvertable;

        use TSqlWhereClause;
    
        protected string $entity;
        public Graph  $graph;
        protected ?SqlConnection $conn;
    
    
        public function __construct(string $entity, array $plan = []) {
            $this->entity = $entity;
            $this->plan($plan);
            $this->conn = null;
        }
    
        public function plan(array $plan): static {
            $this->graph  = new Graph();
            $this->graph->addVertex((new EntityQueryRootVertex($this->entity)));
    
            /** Convert the path into branches so they dont require recursion. */
            $branches = \Arr::map(
                \Arr::branches(
                    \Arr::associate($plan, null, \Fnc::return(), deep: true),
                    \Arr::DOTS_EVAL_ARRAY
                ),
                function($branch) {
                    if(is_array($branch[1]))
                        $branch[1] = null;
    
                    return $branch;
                }
            );
    
            
            foreach($branches as $branchIndex => list($branch, $value)) {
                $branchClass       = $this->entity;
                $nextClassKey      = null;
    
                foreach(\Arr::describe($branch) as $twigIndex => list($position, $twig)) {
                    $branchClassDesign = $branchClass::design();
    
                    $currentClass       = $branchClass;
                    $currentClassDesign = $branchClassDesign;
                    $currentClassKey    = $nextClassKey ?: $currentClass::ref()->toString();
                    
                    if(\Str::startswith($twig, "@")) {
                        $twig = \Str::afterFirst($twig, "@");
    
                        $this->graph->modifyVertex($currentClassKey, function($currentClassVertex) use($branch, $twig, $value) {
                            $validOption = true;

                            try {
                                $validOption = $currentClassVertex->addOption($twig, $value);
                            }
                            catch(Throwable $throwable) {
                                throw new \Error(\Str::format(
                                    "Error while adding option '{}': {}",
                                    \Arr::join($branch, "."),
                                    $throwable->getMessage()
                                ), 0, $throwable);
                            }

                            if(!$validOption) {
                                throw new \Error(\Str::format(
                                    "Unknown option at '{}'.",
                                    \Arr::join($branch, ".")
                                ));
                            }
    
                            return $currentClassVertex;
                        });
    
                        break;
                    }
                    else {
                        /** Get the attribute associated with the current twig **/
                        $joinColumn = $branchClassDesign->getAttrInstance([
                            OneToOne::class,
                            OneToMany::class,
                            Column::class
                        ], $twig, subclasses: true);
    
                        $callback = $value;
    
                        if(($callback !== null ? $callback instanceof Closure : true) === false) {
                            if(\Str::startswith(\Arr::last($branch), "@")) {
                                $callback = null;
                            }
                        }
    
                        if($joinColumn !== null) {
                            if(($joinColumn instanceof OneToMany || $joinColumn instanceof OneToOne) ? $joinColumn->hasForeignChainingProperties() : false) {
                                if(\Integer::hasBits($position, \Arr::POS_END)) {
                                    throw new \Error(
                                        "This feature has not been implemented."
                                    );
                                }
                                else {
                                    throw new \Error(\Str::format(
                                        "Twig {} is an attribute with chaining foreign properties, thus is required to be at the end of the branch",
                                        \Arr::join(["root", ...\Arr::slice($branch, 0, $twigIndex+1)], ".")
                                    ));
                                }
                            }
                            // Check if the column is a foreign key
                            else if($joinColumn->isForeignKey()) {
                                $nextClass    = $branchClass = $joinColumn->getForeignClass();
                                $nextClassKey = \Hex::encode(
                                    crc32($currentClass::ref($joinColumn->parent->getName())->toString(Entity::REF_RESOLVED | Entity::REF_NO_WRAP))
                                );
                                
                                if(!$this->graph->hasVertex($nextClassKey)) {
                                    $joinScope = $currentClassDesign->getAttrInstance(
                                        Scope::class, $joinColumn->parent->getName()
                                    );
    
                                    $nextClassVertex = new EntityQuerySubVertex(
                                        $nextClass,
                                        "!",
                                        $nextClass::design()->getAttrInstance(
                                            Column::class,
                                            $joinColumn->getForeignProperty()
                                        ),
                                        $joinScope
                                   );
                                    $nextClassVertex->id = $nextClassKey;
                                    $nextClassVertex->callback = $callback;
    
                                    $this->graph->addVertex($nextClassVertex);
    
                                    $joinEdge = new EntityQueryEdge(\Hex::encode(openssl_random_pseudo_bytes(4)), $joinColumn);
        
                                    $this->graph->addEdge($currentClassKey, $nextClassKey, $joinEdge);
                                }
                            }
                            else {
                                throw new \Error("Specifying columns is not supported.");
                            }
                        }
                        else {
                            throw new \Error(\Str::format(
                                "Unknown branch path item at {}",
                                \Arr::join(["root", ...\Arr::slice($branch, 0, $twigIndex+1)], ".")
                            ));
                        }
                    }
                }
            }
    
            return $this;
        }
    
        public function vertex(): EntityQueryVertex {
            return $this->graph->getVertex($this->entity::ref());
        }
    
        public function noCache(): static {
            $this->vertex()->noCache();
    
            return $this;
        }
    
        public function highPriority(): static {
            $this->vertex()->highPriority();
    
            return $this;
        }
    
        public function smallResult(): static {
            $this->vertex()->smallResult();
    
            return $this;
        }
    
        public function bigResult(): static {
            $this->vertex()->bigResult();
    
            return $this;
        }
    
        public function bufferResult(): static {
            $this->vertex()->bufferResult();
    
            return $this;
        }
    
        public function orderBy(string|IStringForwardConvertable ...$references): static {
            $this->vertex()->orderBy(...$references);
    
            return $this;
        }
    
        public function orderByAsc(string|IStringForwardConvertable ...$references): static {
            $this->vertex()->orderByAsc(...$references);
    
            return $this;
        }
    
        public function orderByDesc(string|IStringForwardConvertable ...$references): static {
            $this->vertex()->orderByDesc(...$references);
    
            return $this;
        }
    
        public function limit(int|string $limit, int|string $offset = null): static {
            $this->vertex()->limit($limit, $offset);
    
            return $this;
        }

        public function scope(string $name, array $arguments): static {
            $this->vertex()->scopes[] = [$this->entity::design()->getAttrInstance(Scope::class, $name, subclasses: true), $arguments];


            return $this;
        }
    
        public function guide(array $row): array {
            $branches = [];
            $vertex = $this->graph->getVertex($this->entity::ref()->toString());
            $continue   = true;
    
            while($continue && $vertex !== null) {
                $continue = false;
    
                $branch = [
                    $vertex->entity,
                    \Arr::mapAssoc(
                        $vertex->entity::design()->getAttrInstances(Column::class, subclasses: true),
                        function($index, $column) use($vertex) {
                            return [
                                $vertex->entity::ref($column->getColumnName(), Entity::REF_NO_WRAP | Entity::REF_RESOLVED)->toString(),
                                $column->parent->getName()
                            ];
                        }
                    )
                ];
                
                foreach($vertex->edges as $joinVertexKey => $joinVertexEdges) {
                    if(\Arr::hasKey($row, $joinVertexKey)) {
                        if($joinVertexEdgeKey = $row[$joinVertexKey]) {
                            if(\Arr::hasKey($joinVertexEdges, $joinVertexEdgeKey)) {
                                $branch[2] = $joinVertexEdges[$joinVertexEdgeKey];
    
                                $continue = true;
                                
                                $vertex = $this->graph->getVertex($joinVertexKey);
    
                                break;
                            }
                        }
                    }
    
                    
                }
    
                $branches[] = $branch;
            }
    
            return $branches;
        }
    
        public function load(array|Generator|Iterator $rows): array {
            $aggrInstanceKeys = [];
            $aggrInstances = [];
    
            $lastInstance = null;
    
            foreach($rows as $row) {
                $guide = $this->guide($row);
    
                unset($lastInstance);
    
                foreach(\Arr::lead([null, ...$guide]) as list($lastBranch, $nextBranch)) {
                    list($entity, $columns) = $nextBranch;
                    
                    $design = $entity::design();
    
                    $rowSlice = \Arr::mapAssoc(
                        $columns,
                        function($column, $property) use($row) {
                            return [$property, $row[$column]];
                        }
                    );
    
                    $primaryKey = $entity::design()->getPrimaryKey();
                    $primaryKeyValue = $rowSlice[$primaryKey->parent->getName()];
    
                    if($primaryKeyValue !== null) {
    
                        $nextInstanceExists = $design->hasIndex($primaryKeyValue);
    
                        if($nextInstanceExists) {
                            $nextInstance = $design->getInstance($design->resolveIndex($primaryKeyValue));
                        }
                        else {
                            $nextInstance = new $entity();
                            $nextInstance->fromSqlRow($rowSlice);
                            $nextInstance->snap(store: true);
                        }
                        
                        $design->addIndex($nextInstance->getPrimaryKey(), $nextInstance);
    
                        if($lastInstance === null && !\Arr::contains($aggrInstanceKeys, $nextInstancePrimaryKey = $nextInstance->getPrimaryKey())) {
                            $aggrInstances[] = $nextInstance;
                            $aggrInstanceKeys[] = $nextInstancePrimaryKey;
                        }
    
                        if($lastInstance !== null) {
                            $lastJoinColumn = $lastBranch[2]->along;
                            $lastJoinProperty = $lastJoinColumn->parent->getName();
    
                            if(\Cls::isSubclassInstanceOf($lastJoinColumn, OneToOne::class)) {
                                // if(($lastInstance->{$lastJoinProperty} instanceof $entity)) {
                                //     throw new \Error(\Str::format(
                                //         "Multiple instances detected along {}::\${} to {}.",
                                //         $lastJoinColumn->parent->getDeclaringClass()->getName(),
                                //         $lastJoinColumn->parent->getName(),
                                //         $entity
                                //     ));

                                //     // lastJoinProperty
                                // }

                                if($lastInstance->{$lastJoinProperty} === null) {
                                    $lastInstance->{$lastJoinProperty} = $nextInstance;
                                }
                            }
                            else if(\Cls::isSubclassInstanceOf($lastJoinColumn, OneToMany::class)) {
                                if($lastInstance->{$lastJoinProperty}  === null)
                                    $lastInstance->{$lastJoinProperty}  = [];
    
                                $lastInstance->{$lastJoinProperty}[] = $nextInstance;
                            }
                            else {
                                debug($lastJoinColumn::class);
                                throw new \Error();
                            }
    
                        }
    
                        $lastInstance = $nextInstance;
                    }
                }
    
            }
    
            return $aggrInstances;
        }
    
        public function get(): array {
            $raw = $this->toString();
            $conn = $this->conn ?: App::conn();
    
            return $this->load($conn->multiquery($raw, aggr: true, rows: true));
        }
    
        public function first(): ?object {
            $query = clone $this;
            $query->limit(1);
    
            return $query->get()[0];
        }
    
        public function using(string|SqlConnection $conn): static {
            if(is_string($conn))
                $conn = App::conn($conn);
    
            $this->conn = $conn;
    
            return $this;
        }

        public function page(int $size, int $number): array {
            $query = clone $this;
            $query->limit($size, $number > 0 ? ($number * $size) + 1 : 0);

            if($this->conn === null)
                $query->using($this->entity::conn(fallback: true));

            $rows = $query->get();
    
            list($primaryChunk, $secondaryChunk) = \Arr::padRight(
                \Arr::chunk($rows, $size), [], 2);

            $hasNext = !\Arr::isEmpty($secondaryChunk);

            return [$primaryChunk, $hasNext];
        }
    
        public function chunk(int $size, bool $meta = false, bool $aggr = false): Generator {
            $pageSize = $size;
    
            $pageNumber = 0;
            $pageHasNext = true;
    
            do {

                list($pageRows, $pageHasNext) = $this->page($pageSize, $pageNumber);
    
                if($aggr) {
                    foreach($pageRows as $pageRow)
                        yield $pageRow;
                }
                else if($meta) {
                    yield [$pageRows, $pageHasNext];
                }
                else {
                    yield $pageRows;
                }
    
                $pageNumber++;
    
            } while($pageHasNext);
        }
    
        public function take(int $amount): array {
            $query = clone $this;
            $query->limit($amount);
    
            return $query->get();
        }

        public function count(): int {
            if($this->vertex()->hasEdges())
                throw new \Error("Cannot count when joining.");

            $query = DB::select(["`ROWS`" => "COUNT(1)"])->from(\Str::wrapc($this->toString(), "()"), as: "anon");

            return $query->get()->current()["ROWS"] ?: 0;
        }
    
        public function toSqlQueries(string $vertexKey = null, SqlSelectStatement $query = null, array $anchors = []): array {
            $queries = [];
    
            if($vertexKey === null)
                $vertexKey = $this->entity::ref()->toString();
    
            if(!$this->graph->hasVertex($vertexKey))
                throw new \Error("Malformed entity query: graph vertex '$vertexKey' doesnt exist.");
    
            $vertex = $this->graph->getVertex($vertexKey);
    
            if($query === null){
                $query = DB::select();
                $vertex->modifyQuery($query);
    
                if($this->wheres !== null)
                    $query->wheres = clone $this->wheres;
    
                $query->from($vertex->toString(), as: $vertex->entity::ref(flags: Entity::REF_OUTER_WRAP | Entity::REF_RESOLVED)->toString());
            }
            
            $query->columns($vertex->getColumns());
    
            if($vertex->hasEdges()) {
    
                foreach($vertex->edges as $foreignVertexKey => $foreignVertexEdges) {
                    $foreignVertex = $this->graph->getVertex($foreignVertexKey);
    
                    if(!\Arr::isEmpty($foreignVertexEdges)) {
                        list($foreignVertexEdgeKey, $foreignVertexEdge) = \Arr::firstEntry($foreignVertexEdges);
                    
                        $joinColumn = $foreignVertexEdge->along;
    
                        $joinQuery = clone ($query);
                        $foreignVertex->modifyQuery($joinQuery);
    
    
                        $joinForeignProperty = $foreignVertex->entity::design()->getAttrInstance(
                            Column::class,
                             $joinColumn->getForeignProperty()
                        );
    
                        $joinLocalProperty = (
                            (\Cls::isSubclassInstanceOf($joinColumn, OneToAny::class)
                                ? $vertex->entity::design()->getAttrInstance(
                                    [Column::class],
                                    $joinColumn->getLocalProperty()
                                )
                                : $joinColumn
                            )
                        );
    
                        $joinAnchor = \Str::wrap($foreignVertexKey, "`");
                        $joinQuery->column(\Str::wrap($foreignVertexEdgeKey, "'"), as: $joinAnchor);
                        $joinType = $foreignVertex->flag === "?" ? 'leftJoin' : 'innerJoin';
                        $joinCondition = function($condition) use($foreignVertex, $joinLocalProperty, $joinForeignProperty, $vertex) {
                            $condition->orOn(
                                $vertex->entity::{$joinLocalProperty->parent->getName()}(),
                                "=",
                                DB::raw(
                                    $foreignVertex->entity::{$joinForeignProperty->parent->getName()}()->toString()
                                )
                            );
    
                            return $condition;
                        };
    
                        $joinQuery->{$joinType}(
                            $foreignVertex,
                            $joinCondition,
                            as: $foreignVertex->entity::ref(flags: Entity::REF_OUTER_WRAP | Entity::REF_RESOLVED)->toString()
                        );
    
                        
                        $joinAnchors = [...$anchors, $joinAnchor];
    
                        $nextQueries = $this->toSqlQueries($foreignVertexKey, $joinQuery, $joinAnchors);
    
                        /** If no next queries were provided, assume we have reached the end of the plan */
                        if(\Arr::isEmpty($nextQueries)) {
                            $queries[] = [$joinQuery, $joinAnchors];
                        }
                        else {
                            $queries = \Arr::merge(
                                $queries,
                                $nextQueries
                            );
                        }
                    }
                }
            }
            else {
                $queries[] = [$query, $anchors];
            }
    
            return $queries;
        }
    
        public function toString(): string {
            $queries = $this->toSqlQueries();
    
            return \Arr::join(\Arr::map($queries, 0), ";\n");
        }
    }
}

?>