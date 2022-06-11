<?php declare(strict_types = 1);

namespace Slate\Sql\Condition {

    use Closure;

    class SqlBlockCondition extends SqlBaseCondition {
        public Closure $closure;
        public array   $children = [];
    
        public function __invoke($query): mixed {
            ($this->closure)($query);
    
            return null;
        }

        public function buildSql(): ?array {
            $conditions = \Arr::filter(\Arr::map(
                $this->children,
                fn(SqlBaseCondition $child): ?array => $child->buildSql()
            ));

            if(count($conditions) === 0)
                return null;

            return [
                $this->logical,
                \Str::wrapc(
                    \Arr::join(
                        \Arr::slice(
                            array_merge(...$conditions),
                            1
                        ),
                        " "
                    ),
                    "()"
                )
            ];
        }
    }
}

?>