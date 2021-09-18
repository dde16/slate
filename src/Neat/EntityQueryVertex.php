<?php

namespace Slate\Neat {

    use Closure;
    use Slate\Facade\Sql;
    use Slate\Neat\Attribute\Column;
    use Slate\Neat\Attribute\Scope;
    use Slate\Neat\Entity;
    use Slate\Neat\EntityReference;
    use Slate\Sql\Clause\TSqlFromClause;
    use Slate\Sql\Clause\TSqlLimitClause;
    use Slate\Sql\Clause\TSqlOrderByClause;
    use Slate\Sql\Clause\TSqlWhereClause;
    use Slate\Sql\Expression\TSqlColumnsExpression;
    use Slate\Sql\Modifier\TSqlHighPriorityModifier;
    use Slate\Sql\Modifier\TSqlNoCacheModifier;
    use Slate\Sql\Modifier\TSqlResultModifiers;
    use Slate\Sql\SqlReference;
    use Slate\Sql\SqlStatement;

    class EntityQueryVertex extends SqlStatement {
        use TSqlHighPriorityModifier;
        use TSqlResultModifiers;
        use TSqlNoCacheModifier;
        use TSqlColumnsExpression;
        use TSqlFromClause;
        use TSqlWhereClause;
        use TSqlOrderByClause;
        use TSqlLimitClause ;
    
        public string    $id;
        public ?Closure  $callback;
        public string    $entity;
        public array     $edges;
        public array     $scopes;
    
        public function __construct(string $entity) {
            $this->id       = $entity::ref();
            $this->entity   = $entity;
            $this->edges    = [];
            $this->callback = null;
            $this->scopes   = [];
    
            $this->from($entity::ref()->toString());
        }

        public function addOption(string $name, mixed $value): bool {
            $valid = true;

            switch($name) {
                case "flag":
                case "callback":
                    $this->{$name} = $value;
                    break;
                
                case "where":
                case "limit":
                case "offset":
                case "orderBy":
                case "orderByAsc":
                case "orderByDesc":
                    $this->{$name}($value);
                    break;

                case "scopes":
                    $this->scopes = \Arr::merge(
                        $this->scopes,
                        \Arr::map(
                            \Arr::entries(
                                \Arr::associate(
                                    $value,
                                    null
                                )
                            ),
                            function($entry) {
                                list($name, $arguments) = $entry;

                                return [$this->entity::design()->getAttrInstance($name, Scope::class), $arguments];
                            }
                        )
                    );
                    break;

                case "scope":
                    $this->scope[] = $value;
                    break;

                default:
                    $valid = false;
                    break;
            }

            return $valid;

            // if(\Arr::contains(["flag", "callback"], $twig)) {
            //     $currentClassVertex->{$twig} = $value;
            // }
            // else if(\Arr::contains(["where", "limit", "offset", "orderBy", "orderByAsc", "orderByDesc"], $twig)) {
                
            // }
            // else if($twig === "scopes") {
            //     $currentClassVertex->scopes = 
            // }
            // else {
                
            // }
        }
    
        public function hasEdges(): bool {
            return !\Arr::isEmpty($this->edges);
        }
    
        public function getColumns(): array {
            return \Arr::mapAssoc(
                $this->entity::design()->getAttrInstances(Column::class),
                function($key, $column) {
                    return [
                        $this->entity::ref($column->getColumnName(), Entity::REF_OUTER_WRAP)->toString(),
                        // $vertex->entity::ref($column->parent->getName(), Entity::REF_OUTER_WRAP | Entity::REF_RESOLVED),
                        $this->entity::ref($column->getColumnName(), Entity::REF_ITEM_WRAP)->toString()
                    ];
                }
            );
        }
    
        public function toString(): string {
            $build = $this->build();
    
            $buildRelative = $build;
            unset($buildRelative[4]);
            unset($buildRelative[5]);
    
            return
                \Arr::any(\Arr::slice($buildRelative, 1), fn($v) => $v !== null) || $build[4] !== "*"
                    ? \Str::wrapc(\Arr::join(\Arr::filter($build), " "), "()")
                    : $this->entity::ref();
        }
    
        public function build(): array {
            $limit = $this->limit;
            $offset = $this->offset;
            $columns = $this->columns;
            $orderBy = $this->orderBy;
            $wheres = $this->wheres;
            
            $this->orderBy = \Arr::map(
                $this->orderBy,
                function($orderBy) {
                    return $orderBy instanceof EntityReference ? $orderBy->toString(Entity::REF_SQL | Entity::REF_ITEM_WRAP) : $orderBy;
                }
            );
    
            if($this->limit !== null || $this->offset !== null) {
                $this->limit   = null;
                $this->offset   = null;
    
                if($this->flag !== "?" && !\Cls::isSubclassInstanceOf($this, EntityQueryRootVertex::class)) {
                    $rowNumber =
                        Sql::winfn("ROW_NUMBER")
                            ->partitionBy($this->entity::ref($this->column->getColumnName())->toString())
        
                            ->{"orderBy" . ucfirst(\Str::lower($this->orderDirection ?: "ASC"))}(
                                ...(!\Arr::isEmpty($this->orderBy ?: [])
                                    ? \Arr::map(
                                        $this->orderBy,
                                        function($orderBy) {
                                            return ($orderBy instanceof EntityReference) ? $orderBy->toString(Entity::REF_SQL) : $orderBy;
                                        }
                                    )
                                    : [
                                        $this->entity::ref($this->column->getColumnName())->toString()
                                    ]
                                )
                            );
        
                    $this->columns = [
                        (new SqlReference("*")), 
                        (new SqlReference($rowNumber))->as("`RowNumber`" )
                    ];
                    $this->orderBy = [];
                }
            }
    
            $build = [
                "SELECT",
                $this->buildHighPriorityModifier(),
                $this->buildResultModifiers(),
                $this->buildNoCacheModifier(),
                $this->buildColumnsExpression(),
                $this->buildFromClause(),
                $this->buildWhereClause(),
                $this->buildOrderByClause()
            ];
    
            $this->limit = $limit;
            $this->offset = $offset;
            $this->columns = $columns;
            $this->orderBy = $orderBy;
            $this->wheres = $wheres;
    
            if(!\Arr::isEmpty($this->orderBy)) {


                if($this->limit === null)
                    $this->limit = "18446744073709551615";


                $build[] = $this->buildLimitClause();
            }
    
            return $build;
        }
    }
}

?>