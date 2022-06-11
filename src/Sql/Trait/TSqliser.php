<?php

namespace Slate\Sql\Trait {
    trait TSqliser {
        public function toSql(): ?string {
            $built = $this->buildSql();
            
            if($built !== null)
                $built = \Arr::join(\Arr::filter($built), " ");
        
            return $built;
        }
    }
}

?>