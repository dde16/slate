<?php declare(strict_types = 1);

namespace Slate\Sysv\SysvSharedMemoryTable {

    use Generator;
    use Slate\Neat\Entity;
    use Slate\Neat\EntityDesign;
    use Slate\Sql\Clause\TSqlLimitClause;
    use Slate\Sql\Clause\TSqlOrderByClause;
    use Slate\Sysv\SysvSharedMemoryTable;
    use Slate\Utility\TMultiPassthru;

    //TODO: offsets and limits
    class SysvSharedMemoryTableQuery {
        use TSqlLimitClause;
        use TSqlOrderByClause;
    
        use TMultiPassthru;
    
        //TODO: add to class phpdoc 
        public const PASSTHRUS = [
            "where" => [
                "and",
                "or",
                "where",
                "andWhere",
                "orWhere",
                "validate"
            ]
        ];
    
        public SysvSharedMemoryTableWhere $where;
    
        public SysvSharedMemoryTable $table;
    
        public function __construct(SysvSharedMemoryTable $table) {
            $this->table = $table;
            $this->where = new SysvSharedMemoryTableWhere;
        }
    
        /**
         * A function to build the pretests and run them against indexes and counters
         * to find the rows to prioritise for filtering first.
         *
         * @param array $pretests
         *
         * @return Generator
         */
        public function prioritise(array &$pretests = []): Generator {
            $entity = $this->table->getEntity();
    
            /** @var EntityDesign $design */
            $design = $entity::design();
            
            return $this->where->prioritise($pretests, $this->table, $entity, $design);
        }

        public function withinOffset(int $count): bool {
            return ($this->offset !== null ? $count < $this->offset : false);
        }
    
        public function withinLimit(int $count): bool {
            return ($this->limit !== null ? $count < $this->limit : true);
        }

        public function _page(int $size, int $number): static {
            $query = clone $this;
    
            $query->limit(($size * ($number+1))+1, ($size * $number) + intval($number > 0));
    
            return $query;
        }

        public function page(int $size, int $number): array {
            $query = $this->_page($size, $number);
    
            $rows = \Arr::fromGenerator($query->get());
    
            list($primaryChunk, $secondaryChunk) = \Arr::padRight(\Arr::chunk($rows, $size), [], 2);
    
            $hasNext = !\Arr::isEmpty($secondaryChunk);
    
            return [$primaryChunk, $hasNext, $query];
        }
    
        public function get(): Generator {
            /**
             * If is unique then fetch the row then continue without any further planning
             * 
             * If it is indexed, use the dict keys to narrow down the column
             * If its an and equals, check the counters for any zeros
             * 
             * only determines what rows come first
             * 
             * excl: order by
             * excl: go through and check against limit clause
             * 
             */
            $prioritisedHistory  = [];
            $prioritisedPointers = $this->prioritise();
    
            $count               = 0;
            
            while($prioritisedPointers->valid() && $this->withinLimit($count)) {
                /** @var int */
                $prioritisedPointer = $prioritisedPointers->current();
    
                if(!\Arr::contains($prioritisedHistory, $prioritisedPointer)) {
                    $prioritisedHistory[] = $prioritisedPointer;
    
                    $model = $this->table[$prioritisedPointer];
    
                    if($this->where->validate($model))
                        if(!$this->withinOffset($count))
                            yield $model;
    
                    $count++;
                }
    
                $prioritisedPointers->next();
            }

            $this->where->voidPretests();

            foreach($this->table as $pointer => $model) {
                if(!$this->withinLimit($count))
                    break;

                if(!\Arr::contains($prioritisedHistory, intval($pointer))) {
                    if($this->where->validate($model)) {
                        if(!$this->withinOffset($count))
                            yield $model;
                        $count++;
                    }
                }
            }
        }
    
        public function first(): ?Entity {
            return $this->get()->current();
        }
    }
}

?>