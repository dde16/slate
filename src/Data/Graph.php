<?php

namespace Slate\Data {

    use Closure;

    class Graph {
        protected bool   $undirected;

        protected string $primarykey;
        protected string $edgeskey;

        public array  $vertices;

        public function __construct(
            string $primarykey = "id",
            string $edgeskey   = "edges"
        ) {
            $this->primarykey = $primarykey;
            $this->edgeskey   = $edgeskey;
            $this->vertices   = [];
            $this->undirected = false;
        }
        
        /**
         * Get the key for a given vertex object or for a key already provided, return it.
         *
         * @param  mixed $vertex
         * @return string
         */
        public function getVertexKey(array|object|string|int $vertex): string|int { 
            if(\Any::isCompound($vertex)) {
                $vertexKey = \Compound::get($vertex, $this->primarykey);

                if($vertexKey === null){
                    throw new \Error("Vertex primarykey is null.");
                }
            }

            return $vertexKey ?: $vertex;
        }

        public function assertHasVertex(array|object|int|string $vertex): void {
            if(\Arr::hasKey($this->vertices, ($vertexKey = $this->getVertexKey($vertex))) === false)
                throw new \Error("Unknown vertex with key '$vertexKey'.");
        }
        
        /**
         * Check whether two vertices are adjacent.
         *
         * @param  mixed $from
         * @param  mixed $to
         * @return bool
         */
        public function isAdjacent(array|object|int|string $from, array|object|int|string $to): bool {
            $fromKey = $this->getVertexKey($from);
            $fromVertex = &$this->vertices[$fromKey];
            $fromEdges = &$fromVertex[$this->edgeskey];

            $toKey = $this->getVertexKey($to);

            return \Arr::hasKey($fromEdges, $toKey);
        }
        
        /**
         * Add a collection of vertices.
         *
         * @param  mixed $vertices
         * @return void
         */
        public function addVertices(array $vertices): void {
            foreach($vertices as $vertex) {
                $this->addVertex($vertex);
            }
        }
        
        /**
         * Add a vertex.
         *
         * @param  mixed $vertex
         * @return void
         */
        public function addVertex(array|object $vertex): void {
            $vertexKey = $this->getVertexKey($vertex);

            if(!$this->hasVertex($vertexKey)) {
                $this->vertices[$vertexKey] = $vertex;
            }
            else {
                throw new \Error("Vertex by this key already exists.");
            }
        }
        
        /**
         * Check if a vertex object or key exists.
         *
         * @param  mixed $vertex
         * @return bool
         */
        public function hasVertex(array|object|int|string $vertex): bool {
            return \Arr::hasKey($this->vertices, $this->getVertexKey($vertex));
        }

        /**
         * Get a Vertex by its key by reference.
         *
         * @param  mixed $vertexKey
         * 
         * @return mixed
         */
        public function &getVertex(int|string $vertexKey): array|object|null {
            return $this->hasVertex($vertexKey) ? $this->vertices[$vertexKey] : null;
        }

        /**
         * Modify a vertex by key.
         *
         * @param  mixed $vertexKey
         * @param  mixed $callback
         * @return void
         */
        public function modifyVertex(int|string $vertexKey, Closure $callback): void {
            if($this->hasVertex($vertexKey)) {
                $vertex = $this->vertices[$vertexKey];

                if(($vertex = $callback($vertex)) !== null) {
                    $this->vertices[$vertexKey] = $vertex;
                }
            }
        }
        
        /**
         * Remove a vertex.
         *
         * @param  mixed $vertex
         * @return void
         */
        public function removeVertex(array|object|int|string $vertex): void {
            $vertexKey = $this->getVertexKey($vertex);

            if($this->hasVertex($vertexKey)) {
                unset($this->vertices[$vertexKey]);
            }
        }
        
        /**
         * Add an edge from one vertex to another.
         *
         * @param  mixed $from
         * @param  mixed $to
         * @param  mixed $edge
         * @return void
         */
        public function addEdge(array|object|int|string $from, array|object|int|string $to, array|object $edge): void {
            $fromKey = $this->getVertexKey($from);
            $this->assertHasVertex($fromKey);

            $fromVertex = &$this->vertices[$fromKey];
            $fromEdges = &\Compound::get($fromVertex, $this->edgeskey);

            $toKey = $this->getVertexKey($to);
            $this->assertHasVertex($toKey);

            $toVertex = &$this->vertices[$toKey];
            $toEdges = &\Compound::get($toVertex, $this->edgeskey);
            
            \Compound::set($fromEdges, [$toKey, $this->getVertexKey($edge)], $edge, []);
            
            if($this->undirected)  {
                \Compound::set($toEdges, [$fromKey, $this->getVertexKey($edge)], $edge);
            }
        }

        /**
         * Modify an edge.
         *
         * @param  mixed $from
         * @param  mixed $to
         * @param  mixed $edgekey
         * @return void
         */
        public function modifyEdge(array|object|int|string $from, array|object|int|string $to, int|string $edgekey, Closure $callback): void {
            $fromKey = $this->getVertexKey($from);
            $toKey = $this->getVertexKey($to);
            
            $edge = \Compound::get($this->vertices[$fromKey], [$this->edgeskey, $toKey, $edgekey]);

            if(($edge = $callback($edge)) !== null) {
                \Compound::set($this->vertices[$fromKey], [$this->edgeskey, $toKey, $edgekey], $edge);
            }
        }
        
        /**
         * Remove an edge between one vertex and another.
         *
         * @param  mixed $from
         * @param  mixed $to
         * @param  mixed $edge
         * @return void
         */
        public function removeEdge(array|object|int|string $from, array|object|int|string $to, array|object|int|string $edge): void {
            $fromKey = $this->getVertexKey($from);
            $fromVertex = &$this->vertices[$fromKey];
            $fromEdges = \Compound::get($fromVertex, $this->edgeskey);

            $toKey = $this->getVertexKey($to);
            $toVertex = $this->vertices[$toKey];
            $toEdges = \Compound::get($toVertex, $this->edgeskey);

            \Compound::unset($fromEdges, [$toKey, $this->getVertexKey($edge)]);

            if($this->undirected) {
                \Compound::unset($toEdges, [$fromKey, $this->getVertexKey($edge)]);
            }

        }
    }
}

?>