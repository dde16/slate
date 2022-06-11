<?php declare(strict_types = 1);

namespace Slate\Facade {

    use Slate\Sql\SqlConnection;
    use Slate\Utility\Singleton;
    /** @method static SqlConnection conn(?string $name = null) */
    class App extends Singleton {
        public const DEFAULT = \Slate\Foundation\App::class;
        public const MANUAL  = true;
    }
}

?>