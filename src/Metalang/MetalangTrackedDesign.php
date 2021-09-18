<?php

namespace Slate\Metalang {
    
    class MetalangTrackedDesign extends MetalangDesign {
        protected array $instances = [];

        public function addInstance(object $instance): void {
            $id       = spl_object_id($instance);

            $this->instances[$id] = $instance;
        }

        public function removeInstance(object $instance): void {
            $id       = spl_object_id($instance);

            unset($this->instances[$id]);
        }

        public function &getInstance(int $key): object|null {
            return \Arr::hasKey($this->instances, $key) ? $this->instances[$key] : null;
        }

        public function getInstances(): array {
            return $this->instances;
        }

    }
}

?>