<?php

namespace Slate\Mvc {

    use Slate\Data\Collection;
    use Slate\Http\HttpEnvironment;
    use Slate\Utility\TImitate;
    use Slate\Utility\TUninstantiable;

    final class Env {
        use TImitate;

        public static function createInstance(): object {
            return(new Collection([
                "env.host" => HttpEnvironment::getHost()
            ], Collection::APPENDABLE));
        }

        public static array $boundpaths = [
            "mvc.root.path" => [
                "mvc.view.path",
                "mvc.public.path"
            ]
        ];

        public static function bind(string $root, string $child): void {
            static::$boundpaths[$root][] = $child;
        }
    }
}

?>