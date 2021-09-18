<?php

namespace Slate\Facade {

    use Closure;
    use Slate\Utility\Facade;

    final class Http extends Facade {
        public static function toClientCookieString(array $cookies): string {
            return \Arr::join(
                \Arr::map(
                    \Arr::entries($cookies),
                    function($entry) {
                        return \Arr::join(
                            \Arr::map($entry, Closure::fromCallable('urlencode')),
                            "="
                        );
                    }
                ),
                "; "
            );
        }
    }
}

?>