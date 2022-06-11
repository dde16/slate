<?php declare(strict_types = 1);

namespace Slate\Facade {
    use Slate\Utility\Singleton;
    use Slate\Mvc\Router as RouterObject;

    /**
     * @method static void map(Closure $map)
     * @method static void pattern(string $name, string $pattern)
     * @method static Slate\Data\Collection many(string $pattern, array $targets)
     * @method static Slate\Mvc\Route view(string $pattern, string $view, array $data)
     * @method static Slate\Mvc\RouteGroup group(array $options, Closure $group)
     * @method static Slate\Mvc\RouteGroup name(string $name, Closure $group)
     * @method static Slate\Mvc\RouteGroup prefix(string $prefix, Closure $group)
     * @method static Slate\Mvc\RouteGroup domain(string $domain, Closure $group)
     * @method static void redirect(string $pattern, string $redirect)
     * @method static mixed get(array|string $patterns, Closure|array|string $target)
     * @method static mixed post(array|string $patterns, Closure|array|string $target)
     * @method static mixed patch(array|string $patterns, Closure|array|string $target)
     * @method static mixed put(array|string $patterns, Closure|array|string $target)
     * @method static mixed delete(array|string $patterns, Closure|array|string $target)
     * @method static mixed add(array|string $patterns, Closure|array|string $target)
     * @method static Slate\Mvc\RouteStructure jit()
     * @method static void fallback(Closure|Slate\Mvc\Route|array|string $fallback)
     * @method static array routes()
     * @method static ?array match(Slate\Http\HttpRequest $request)
     */
    class Router extends Singleton {
        public const DEFAULT = RouterObject::class;
    }
}

?>