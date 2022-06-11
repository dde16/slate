<?php declare(strict_types = 1);

namespace Slate\Sql\Operator {

    use Slate\Sql\Contract\ISqlable;
    use Slate\Sql\SqlConstruct;

    class SqlUnionOperator extends SqlConstruct {
        protected array $sqlables;
        protected string $type;

        public function __construct(array $sqlables, string $type = "ALL") {
            $this->sqlables = $sqlables;
            $this->type = $type;
        }

        public function buildSql(): ?array {
            $sqlables = \Arr::filter(\Arr::map($this->sqlables, fn(ISqlable $sqlable): ?string => $sqlable->toSql()));

            if(\Arr::isEmpty($sqlables))
                return null;

            return \Arr::delimit($sqlables, "UNION {$this->type}");
        }
        
    }
}

?>