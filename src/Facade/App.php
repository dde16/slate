<?php

namespace Slate\Facade {

    use Slate\Utility\Singleton;

    class App extends Singleton {
        public const DEFAULT = \Slate\Foundation\App::class;
        public const MANUAL  = true;
    }
}

?>