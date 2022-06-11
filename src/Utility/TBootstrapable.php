<?php declare(strict_types = 1);

namespace Slate\Utiltiy {

    use Closure;

    trait TBootstrapable {
        protected static array $bootstraps = [];

        public function __construct() {
            if(\Arr::hasKey(static::$bootstraps, static::class)) {
                \Fnc::chain(
                    \Arr::map(
                        static::$bootstraps[static::class],
                        fn(Closure $closure) => Closure::bind($closure, $this)
                    ),
                    func_get_args()
                );
            }
        }
    }
}

?>