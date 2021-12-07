<?php

namespace Slate\Mvc {

    use Slate\Data\Collection;
    use Slate\Http\HttpEnvironment;
    use Slate\Utility\Singleton;

    final class Env extends Singleton {
        public const DEFAULT = Collection::class;

        public static function make(array $arguments = []): object {
            return parent::make([
                ["env.host" => HttpEnvironment::getHost()],
                Collection::APPENDABLE
            ]);
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