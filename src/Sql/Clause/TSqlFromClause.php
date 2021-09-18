<?php

namespace Slate\Sql\Clause {

    use Slate\Data\IStringForwardConvertable;
    use Slate\Sql\SqlReference;
    use Slate\Sql\SqlStatement;

trait TSqlFromClause {
        protected array $froms = [];

        public function from(string|IStringForwardConvertable $reference, string $as = null): object {
            $from = $this->froms[] = new SqlReference($reference);

            if($as) $from->as($as);

            return $this;
        }
        
        public function table(string|IStringForwardConvertable $reference, string $as = null): object {
            return $this->from($reference, $as);
        }

        public function buildFroms(): string {
            return \Arr::join(
                \Arr::map(
                    $this->froms,
                    function($from){
                        return $from->toString();
                    }
                ),
                ", "
            );
        }

        
        public function buildFromClause(): string|null {            
            return !\Arr::isEmpty($this->froms) ? "FROM " . $this->buildFroms() : null;
        }
    }
}

?>