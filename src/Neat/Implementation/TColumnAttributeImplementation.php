<?php declare(strict_types = 1);

namespace Slate\Neat\Implementation {

    use Closure;
    use Slate\Metalang\Attribute\HookCallStatic;
    use Slate\Metalang\Attribute\HookGet;
    use Slate\Neat\Attribute\Column;
    use Slate\Neat\Attribute\OneToAny;
    use Slate\Neat\Attribute\OneToOne;
    use Slate\Neat\EntityDesign;
    trait TColumnAttributeImplementation {
        #[HookGet(OneToAny::class)]
        public function eagerLoadingImplementor(string $name, object $next): mixed {
            $design = static::design();

            /** @var OneToAny */
            if(($relationship = $design->getAttrInstance(OneToAny::class, $name)) !== null) {
                list($match, $result) = $next($name);

                if(!$match) {
                    if($this->{$name} === null) {
                        $foreignClass = $relationship->getForeignClass();
                        $foreignDesign = $relationship->getForeignDesign();
                        $foreignColumn = $foreignDesign->getColumnProperty($relationship->getForeignProperty());
    
                        $query = $foreignClass::query();

                        $localPropertyValue = $this->{$relationship->getLocalProperty()};

                        if($localPropertyValue !== null) {
                            $query->where($foreignColumn->getColumnName(), $localPropertyValue);

                            $this->{$name} = $query->{$relationship instanceof OneToOne ? "first" : "get"}();
                        }
                    }

                    $result = $this->{$name};
                }

                return [true, $result];
            }
            
            return $next($name);
        }

        #[HookCallStatic(Column::class)]
        public static function columnStaticImplementor(string $name, array $arguments, object $next): mixed {
            $design = static::design();

            if($columnAttribute = $design->getAttrInstance(Column::class, $name)) {

                return [true, static::conn()->wrap($columnAttribute->getColumnName())];
            }

            return ($next)($name, $arguments);
        }
    }
}

?>