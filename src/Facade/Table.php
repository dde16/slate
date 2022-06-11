<?php

namespace Slate\Facade {

    use Closure;
    use Slate\Utility\Facade;

    class Table extends Facade {
        public static function create(string $table, Closure $callback = null): void {
            $conn = App::conn();
            $table = $conn->table(...$conn->unwrap($table));

            if($callback)
                $callback($table);

            if(!$table->exists()) {
                $table->create();
            }
            
            $table->commit();
        }
        
        public static function drop(string $table): void {
            $conn = App::conn();
            $table = $conn->table(...$conn->unwrap($table));
            $table->drop();
        }
    }
}

?>