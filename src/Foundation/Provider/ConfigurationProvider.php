<?php

namespace Slate\Foundation\Provider {

    use Slate\Exception\IOException;
    use Slate\Foundation\Provider;
    use Slate\Mvc\Env;

    class ConfigurationProvider extends Provider {
        protected array $required = [
            "mvc.root.path",
            "mvc.index.path",
            "mvc.public.path",
            "mvc.view.path"
        ];

        public function register(): void {
            /** Ensure the root path is valid */
            $root = \Path::resolve($this->app->root());

            if($root !== null) {
                $this->app->root($root);

                if(!is_dir($root))
                    throw new IOException("Root directory '".$this->app->root()."' was not found.", IOException::ERROR_DIR_NOT_FOUND);
                
                Env::set("mvc.root.path", \Path::normalise($root));
            }
            else {
                throw new IOException("Unable to resolve the root path '".$this->app->root()."'.", IOException::ERROR_UNRESOLVABLE_PATH);
            }

            /** Check if the required config variables are set. */
            if(Env::has($this->required)) {
                /** Normalise the index path incase of irregular leading or trailing slashes */
                Env::modify("mvc.index.path", function($value) {
                    return \Path::normalise($value);
                });

                /** Resolve any paths bound to its root */
                foreach(Env::$boundpaths as $rootkey => $children) {
                    /** Get the root path */
                    $root = Env::string($rootkey, assert: true);

                    foreach(array_merge($children) as $index => $key) {
                        $value = Env::get($key);
                        $key   = \Arr::join(\Arr::slice(\Str::split($key, "."), 0, -1), ".").".relative.path";
    
                        Env::set($key, $value);
                    }
    
                    /** Modify all of these values to make absolute paths */
                    Env::modify($children, function($value, $key) use($root) {
                        return $root.$value;
                    });
                }
            }
            else {
                throw new \UnexpectedValueException(
                    "The configuration file does not have the required variables "
                    . \Arr::join(
                        \Arr::filter(
                            $this->required,
                            fn(string $required): bool => !Env::has($required)
                        ),
                        ", "
                    )
                );
            }
        }
    }
}

?>