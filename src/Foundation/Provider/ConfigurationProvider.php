<?php declare(strict_types = 1);

namespace Slate\Foundation\Provider {

    use Slate\Exception\IOException;
    use Slate\Foundation\Provider;
    use Slate\Mvc\Env;

    class ConfigurationProvider extends Provider {
        protected array $required = [
            "mvc.path.views",
            "mvc.path.public"
        ];

        protected string $absolutes = "mvc.path.absolute";

        protected array $relatives = [
            "mvc.path.absolute.root" => [
                "mvc.path.views",
                "mvc.path.public"
            ]
        ];

        public function register(): void {
            /** Ensure the root path is valid */
            $root = \Path::resolve($this->app->root());

            if($root !== null) {
                $this->app->root($root);

                if(!is_dir($root))
                    throw new IOException("Root directory '".$this->app->root()."' was not found.", IOException::ERROR_DIR_NOT_FOUND);
                
                Env::set("mvc.path.absolute.root", \Path::normalise($root));
            }
            else {
                throw new IOException("Unable to resolve the root path '".$this->app->root()."'.", IOException::ERROR_UNRESOLVABLE_PATH);
            }

            /** Resolve any paths bound to its root */
            foreach($this->relatives as $rootKey => $childKeys) {
                /** Get the root path */
                $root = Env::get($rootKey);

                foreach(array_merge($childKeys) as $childKey) {
                    $value = \Path::normalise(Env::get($childKey));
                    $key   = $this->absolutes.".".\Str::afterLast($childKey, ".");

                    Env::set($key, $root.$value);
                }
            }
        }
    }
}

?>