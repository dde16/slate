<?php

namespace Slate\Foundation {

    use Slate\Metalang\MetalangClass;
    use Slate\Metalang\Attribute\AttributeCall;

    abstract class Kernel extends MetalangClass {
        public const NONE      = 0;
    
        public const STAGES = [];
    
        protected int $stage = 0;
                
        /**
         * Check whether the kernel is past a given stage.
         *
         * @param  mixed $stage
         * @return bool
         */
        public function past(int $stage): bool {
            return \Integer::hasBits($this->stage, $stage);
        }
            
        /**
         * Run all stages.
         *
         * @return void
         */
        public function stage(): void {
            foreach(static::STAGES as $stage) {
                if(!$this->past($stage)) {
                    $stager = \Arr::first(
                        static::design()->getAttrInstances(Stager::class),
                        function($stager) use($stage) {
                            return $stager->is($stage);
                        }
                    );
    
                    if($stager) {
                        $this->{$stager->parent->getName()}();
                        $this->stage |= $stage;
                    }
                }
            }
        }
        
        public function go(): void {
            $this->stage();
        }

        #[AttributeCall(Stager::class)]
        public function stagerCallImplementor(string $name, array $arguments, object $next): array {
            $design = static::design();

            if(($stager = $design->getAttrInstance(Stager::class, $name)) !== null) {
                if(!$this->past($flag = $stager->getFlag())) {
                    $this->{$stager->parent->getName()}();
                    $this->stage |= $flag;
                }

                return [true, null];
            }

            return $next($name, $arguments);
        }
    }
}

?>