<?php declare(strict_types = 1);

namespace Slate\Neat {
    class EntityCarryQuery extends EntityQuery {
        protected array $chain;

        public function __construct(string $entity) {
            parent::__construct($entity);

            $this->chain = [];
            // $this->addChainLink($chainlink);
        }

        public function addChainLink(string $chainlink): void {
            $this->chain[] = $chainlink;
            $this->plan(\Arr::drill($this->chain, null));
        }
    }
}

?>