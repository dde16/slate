<?php

namespace Slate\Facade {

    use Slate\Foundation\Dispatcher;
    use Slate\Utility\Singleton;

    /**
     * @see Dispatcher
     */
    class Events extends Singleton {
        public const DEFAULT = Dispatcher::class;
    }
}

?>