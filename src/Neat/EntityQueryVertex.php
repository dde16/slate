<?php

namespace Slate\Neat {

    use Closure;
    use Slate\Data\IAnyForwardConvertable;
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
    use Slate\Sql\SqlModifier;
    use Slate\Sql\SqlStatement;
    use Slate\Sql\Statement\SqlSelectStatement;

    class EntityQueryVertex extends SqlStatement implements IAnyForwardConvertable {
        public const MODIFIERS =
            SqlModifier::HIGH_PRIORITY
            | SqlModifier::NO_CACHE
            | SqlModifier::BIG_RESULT
            | SqlModifier::SMALL_RESULT
            | SqlModifier::BUFFER_RESULT
        ;

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

        public EntityQueryVertex $nextVertex;
    
        public function __construct(string $entity) {
            $this->id       = $entity::ref();
            $this->entity   = $entity;
            $this->edges    = [];
            $this->callback = null;
            $this->scopes   = [];
    
            $this->from($entity::ref()->toString());
        }

        
        public function __clone() {
            if($this->wheres !== null)
                $this->wheres = clone $this->wheres;
        }

        public function addOption(string $name, mixed $value): bool {
            $valid = true;

            switch($name) {
                case "callback":
                case "flag":
                case "orderDirection":
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
        public function modifyQuery(SqlSelectStatement $query, EntityQueryVertex $foreignVertex = null): void {
            foreach($this->scopes as list($scope, $arguments)) 
                $scope->parent->invokeArgs(null, [$query, ...$arguments]);
        }

        public function toAny(): mixed {
            return $this->toString();
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
            // debug("Building {$this->entity}");
            
            $this->orderBy = \Arr::map(
                $this->orderBy,
                function($orderBy) {
                    return $orderBy instanceof EntityReference ? $orderBy->toString(Entity::REF_SQL | Entity::REF_ITEM_WRAP) : $orderBy;
                }
            );

            $build = [
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
                $this->buildWhereClause(),
                $this->buildOrderByClause()
            ];

            if(!\Arr::isEmpty($this->orderBy) && $this->hasEdges()) {
                $this->limit = "18446744073709551615";

                $build[] = $this->buildLimitClause();
            }

            return $build;
        }
    }
}

?>