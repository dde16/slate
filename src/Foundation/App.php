<?php declare(strict_types = 1);

namespace Slate\Foundation {

    use Slate\Metalang\MetalangClass;
    use Slate\Mvc\Env;

    class App extends MetalangClass {
        protected string $root;
        protected array  $providers = [];

        public const PROVIDERS = [
            \Slate\Foundation\Provider\ConfigurationProvider::class,
            \Slate\Foundation\Provider\ConnectionProvider::class,
            \Slate\Foundation\Provider\ShmProvider::class,
            \Slate\Foundation\Provider\QueueProvider::class,
            \Slate\Foundation\Provider\RepositoryProvider::class
        ];

        public function root(string $root = null): ?string {
            if ($root === null) {
                return $this->root;
            }

            $this->root = $root;

            return null;
        }

        public function __construct(string $root) {
            $this->root = $root;

            $injectProviders = Env::var("apps")->fallback([])->array();

            $providers = [
                ...static::PROVIDERS,
                ...(@$injectProviders["*"]) ?? [],
                ...(@$injectProviders[static::class]) ?? []
            ];

            foreach ($providers as $providerClass) {
                $providerInstance = &$this->providers[$providerClass];

                if ($providerInstance === null) {
                    $providerInstance = new $providerClass($this);
                }

                if (\Cls::hasMethod($providerClass, "register")) {
                    $providerInstance->register();
                }
            }


            foreach ($providers as $providerClass) {
                $providerInstance = &$this->providers[$providerClass];

                if (\Cls::hasMethod($providerClass, "boot")) {
                    $providerInstance->boot();
                }
            }
        }
    }
}

?>