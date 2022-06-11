<?php

namespace Slate\Facade {

    use Closure;
    use Slate\Utility\Facade;

    class Schema extends Facade {
        public static function create(string $schema, Closure $callback = null): void {
            $schema = App::conn()->schema($schema);

            if($callback)
                $callback($schema);

            if(!$schema->exists()) {
                $schema->create();
            }
            else {
                $schema->commit();
            }
        }
        
        public static function drop(string $schema): void {
            App::conn()->schema($schema)->drop();
        }
    }
}

?>