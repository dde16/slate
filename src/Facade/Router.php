<?php

namespace Slate\Facade {
    use Slate\Utility\Singleton;
    use Slate\Mvc\Router as RouterObject;

    /** @method static void pattern(string $name, string $pattern) */
    /** @method static Route many(string $pattern, array $targets) */
    /** @method static Route view(string $pattern, string $view = null, array $data = []) */
    /** @method static RouteGroup group(array $options, Closure $group) */
    /** @method static RouteGroup name(string $name, Closure $group) */
    /** @method static RouteGroup prefix(string $prefix, Closure $group) */
    /** @method static RouteGroup domain(string $domain, Closure $group) */
    /** @method static void redirect(string $pattern, string $redirect = null) */
    /** @method static ?Route add(string|array $patterns, string|array|Closure $targets) */
    /** @method static void jit() */
    /** @method static void fallback(Closure|Route|array|string $fallback) */
    /** @method static ?array routes() */
    /** @method static void build() */
    /** @method static array|null match(HttpRequest $request) */
    class Router extends Singleton {
        public const DEFAULT = RouterObject::class;
    }
}

?>