<?php

namespace Slate\Neat {

    use Generator;
    use Iterator;
    use PDO;
    use Slate\Facade\DB;
    use Slate\Neat\Attribute\Column;
    use Slate\Neat\Attribute\OneToAny;
    use Slate\Neat\Attribute\OneToOne;
    use Slate\Neat\Attribute\PrimaryColumn;
    use Slate\Neat\Attribute\Scope;
    use Slate\Sql\Clause\TSqlFromClause;
    use Slate\Sql\Clause\TSqlLimitClause;
    use Slate\Sql\Clause\TSqlOrderByClause;
    use Slate\Sql\Clause\TSqlWhereClause;
    use Slate\Sql\Expression\TSqlColumnsExpression;
    use Slate\Sql\SqlModifier;
    use Slate\Sql\SqlStatement;
    use Slate\Sql\Statement\TSqlSelectStatementCount;
    use Slate\Sql\Statement\TSqlSelectStatementGet;
    use Slate\Sql\Statement\TSqlSelectStatementPluck;

    abstract class EntityQueryVertex extends SqlStatement {
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
    
        use TSqlSelectStatementGet;
        use TSqlSelectStatementPluck;
        use TSqlSelectStatementCount;
    
        /**
         * The left hand entity.
         *
         * @var string
         */
        public string $entity;
    
        /**
         * Set of scopes
         *
         * @var array
         */
        public array $scopes = [];
    
        /**
         * All child relationships.
         *
         * @var EntityRelationship[]
         */
        public array $children = [];
    
        public function __construct(string|Entity $target) {
            if(is_object($target)) {
                $target = get_class($target);
            }
    
            $this->entity = $target;
    
            $design = $target::design();
    
            $this->from($target::table()->fullname());
    
            $this->conn = $target::conn();
    
            $this->column($this->conn->wrap($design->getPrimaryKey()->getColumnName()));
        }

        public function scope(string $name, array $arguments): static {
            $this->scopes[] = [$name, $arguments];

            return $this;
        }
    
        /**
         * Get relationship columns that are set for the current plan.
         *
         * @return array
         */
        public function getRelationshipColumns(): array {
            $localDesign = $this->entity::design();

            $localColumns = [];

    
            foreach($this->children as $relationship) {
                $localColumn = $localDesign->getAttrInstance(Column::class, $relationship->along->getLocalProperty());


                if(($localColumn instanceof PrimaryColumn) === false)
                    $localColumns[] = $localColumn;
            }

            return $localColumns;

        }
    
        public function getNonRelationshipColumns() {
            $design  = $this->entity::design();
            $relationships = $this->children;
            $columns = \Arr::filter(
                $design->getAttrInstances(Column::class),
                function(Column $column) use($design, $relationships): bool {
                    return !($column instanceof PrimaryColumn) ? \Arr::none(
                        $relationships,
                        fn($relationship): bool => $relationship->along->getLocalProperty() === $column->parent->getName()
                    ) : false;
                }
            );
    
            $primaryColumn = $design->getPrimaryKey();
            $columns = [$primaryColumn->parent->getName() => $primaryColumn] + $columns;

            return $columns;
        }
    
        public function query() {
            $query = clone $this;
            
            $localDesign = $this->entity::design();
    
            if($this->along) {
                $foreignClass = $this->along->getForeignClass();
                $foreignProperty = $this->along->getForeignProperty();
                $foreignDesign = $foreignClass::design();
                $foreignColumn = $foreignDesign->getAttrInstance(Column::class, $foreignProperty);
    
                $query->where($foreignColumn->getColumnName(), DB::raw("?"));
            }
    
    
            foreach($this->scopes as $anonScope) {
                if(is_string($anonScope))
                    $anonScope = [$anonScope, []];

                [$scopeName, $scopeArguments] = $anonScope;

                if(($scope = $localDesign->getAttrInstance(Scope::class, $scopeName)) !== null) {
                    $this->entity::{$scope->parent->getName()}($query, ...$scopeArguments);
                }
            }
    
            if(!\Arr::isEmpty($this->children))
                $query->limit = null;
    
            foreach($this->getRelationshipColumns() as $relationshipColumn) {
                $query->column($this->conn->wrap($relationshipColumn->getColumnName()));
            }
    
            return $query;
        }
    
        public function children(Iterator $parentModels = null): Generator {
            $required  = \Arr::filter($this->children, fn(EntityQuerySubVertex $vertex) => !$vertex->optional);
            $optionals = \Arr::filter($this->children, fn(EntityQuerySubVertex $vertex) => $vertex->optional);
    
            $localDesign = $this->entity::design();
    
            $relationships = [
                ...\Arr::entries($required),
                ...\Arr::entries($optionals)
            ];
    
            foreach($relationships as [$name, $relationship]) {
                $query = $relationship->query();
    
                if(($alongScope = $localDesign->getAttrInstance(Scope::class, $relationship->along->parent->getName())) !== null) {
                    $this->entity::{$alongScope->parent->getName()}($query);
                }
    
                $relationship->statement = $this->conn->prepare($query->toString());
            }
    
            $parentModelCount = 0;
    
            while (($this->limit !== null ? ($parentModelCount < $this->limit) : true) && $parentModels->valid()) {
                $parentModel = $parentModels->current();
                $parentModel = new EntityQueryModel(["properties" => $parentModel, "vertex" => $this]);
    
                $atomic = true;
    
                foreach($relationships as [$name, $relationship]) {
                    $statement = $relationship->statement;
    
                    $foreignClass = $relationship->along->getForeignClass();
                    $foreignProperty = $relationship->along->getForeignProperty();
                    $foreignDesign = $foreignClass::design();
                    $foreignColumn = $foreignDesign->getAttrInstance(Column::class, $foreignProperty);
    
                    $localColumn = $localDesign->getAttrInstance(Column::class, $relationship->along->getLocalProperty());
                    $localColumnValue = $parentModel->properties[$localColumn->getColumnName()];
    
    
                    if($localColumnValue !== null) {
                        $statement->execute([$localColumnValue]);
    
                        if($relationship->optional === false ? $statement->rowCount() !== 0 : true) {
                            $parentModel->relationships[$name] = iterator_to_array(
                                $relationship->children(
                                    (function() use($statement): Generator {
                                        while(($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false)
                                            yield $row;
                                    })()
                                )
                            );
    
                            if($relationship->optional === false ? (\Arr::isEmpty($parentModel->relationships[$name]) || \Arr::any($parentModel->relationships[$name], fn($v) => $v == null)) : false) {
                                $atomic = false;
                            }
                        }
                        else {
                            $atomic = false;
                        }
                
                        $statement->fetch();
                        $statement->closeCursor();
                    }
                    else if(!$relationship->optional) {
                        $atomic = false;
                    }
    
                    if(!$atomic)
                        break;
                }
    
                if($atomic) {
                    yield $parentModel;
                    $parentModelCount++;
                }
    
                $parentModels->next();
            }
        }
    
        public function option(string $name, mixed $value): void {
            switch($name) {
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
                    $this->scopes = array_merge($this->scopes, \Arr::always($value));
                    break;
                
                case "flag":
                    $this->optional = $value === "?";
                    break;
    
                default:
                    break;
            }
        }
    
        public function build(): array {
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
                $this->buildWhereClause(),
                $this->buildOrderByClause(),
                $this->buildLimitClause()
            ];
        }
    
        public function fill(array $models) {
            $sources = [];
    
            \Compound::modifyRecursive($models, function(string $key, mixed &$incompleteModel, array $path) use(&$sources) {
                if(is_object($incompleteModel) ? $incompleteModel instanceof EntityQueryModel : false) {
                    $entity = $incompleteModel->vertex->entity;
    
                    if(!\Arr::hasKey($sources, $entity))
                        $sources[$entity] = [
                            "relationship" => $incompleteModel->vertex,
                            "models" => []
                        ];
    
                        $primaryKeyValue = $incompleteModel->properties[$incompleteModel->vertex->entity::design()->getPrimaryKey()->getColumnName()];
    
                    if($sources[$entity]["models"][$primaryKeyValue] === null)
                        $sources[$entity]["models"][$primaryKeyValue] = &$incompleteModel;
                    else 
                        $incompleteModel = $sources[$entity]["models"][$primaryKeyValue];

                }
            },"bottom-up");
    
            foreach($sources as $entity => $source) {
                $relationship = $source["relationship"];
    
                $query = DB::select()->from($relationship->entity::table()->fullname());
    
                foreach($relationship->getNonRelationshipColumns() AS $column) {
                    $query->column($this->conn->wrap($column->getColumnName()));
                }
    
                $primaryKey = $relationship->entity::design()->getPrimaryKey();
    
                $query->where($this->conn->wrap($primaryKey->getColumnName()), "IN", \Arr::keys($source["models"]));
    
                foreach($query->get() as $row) {
                    $model = &$sources[$entity]["models"][$row[$primaryKey->getColumnName()]];
                    $model->properties = \Arr::merge($row, $model->properties);
                }
            }
    
            return $models;
    
        }
    
        public function apply(array $models) {
            return \Arr::map(
                $models,
                function(EntityQueryModel $model): Entity {
                    $inst = new $this->entity;
                    $inst->fromSqlRow($model->properties);
    
                    foreach($this->children as $name => $relationship) {
                        $children = $relationship->apply($model->relationships[$name] ?? []);
    
                        $inst->{$name} = $relationship->along instanceof OneToOne ? $children[0] : $children;
                    }
    
                    return $inst;
                }
            );
        }
    }
}

?>