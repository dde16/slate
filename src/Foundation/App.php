<?php

namespace Slate\Foundation {

    use Slate\Metalang\MetalangClass;
    use Slate\Mvc\Env;

    class App extends MetalangClass {
        protected string $root;
        protected array  $providers = [];

        public const PROVIDERS = [
            \Slate\Foundation\Provider\ConfigurationProvider::class,
            \Slate\Foundation\Provider\ConnectionProvider::class,
            \Slate\Foundation\Provider\QueueProvider::class,
            \Slate\Foundation\Provider\RepositoryProvider::class
        ];

        public function root(string $root = null): ?string {
            if($root === null)
                return $this->root;

            $this->root = $root;

            return null;
        }

        public function __construct(string $root) {
            $this->root = $root;
            $providers = [
                ...static::PROVIDERS,
                ...(@(Env::array("apps") ?? [])[static::class] ?? [])
            ];

            foreach($providers as $providerClass) {
                $providerInstance = &$this->providers[$providerClass];

                if($providerInstance === null)
                    $providerInstance = new $providerClass($this);

                if(\Cls::hasMethod($providerClass, "register"))
                    $providerInstance->register();
            }


            foreach($providers as $providerClass) {
                $providerInstance = &$this->providers[$providerClass];

                if(\Cls::hasMethod($providerClass, "boot"))
                    $providerInstance->boot();
            }
        }
    }
}

?>