<?php

namespace Slate\Mvc {

    use Slate\Data\Collection;
    use Slate\Http\HttpEnvironment;
    use Slate\Utility\Singleton;

    final class Env extends Singleton {
        public const DEFAULT = Collection::class;

        public static function fromArray(array $array): void {
            $dots = \Arr::dotsByValue($array);

            \Arr::mapRecursive(
                $array,
                function(string|int $key, mixed $value) use(&$dots) {
                    if(is_string($value))
                        $value = \Str::format($value, $dots);

                    return [$key, $value];
                }
            );

            static::instance()->fromArray($array);
        }

        public static function make(array $arguments = []): object {
            return parent::make([
                ["env.host" => HttpEnvironment::getHost()],
                Collection::APPENDABLE
            ]);
        }
    }
}

?>