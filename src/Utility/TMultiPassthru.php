<?php declare(strict_types = 1);

namespace Slate\Utility {

    use Slate\Exception\UndefinedRoutineException;

    trait TMultiPassthru {
        public function __call(string $method, array $arguments): mixed {
            $passthrus = \Cls::getConstant(static::class, "PASSTHRUS", []);
            $returnThisOn = \Cls::getConstant(static::class, "PASSTHRU_RETURN_THIS");

            [$passthru] = \Arr::firstEntry(
                $passthrus,
                fn(array $methods, string $passthru): bool => \Arr::contains($methods, $method)
            );

            if($passthru === null)
                throw new UndefinedRoutineException([static::class, $method], UndefinedRoutineException::ERROR_UNDEFINED_METHOD);

            $result = $this->{$passthru}->{$method}(...$arguments);

            if(is_string($returnThisOn) ? \Str::match($method, $returnThisOn) : ($returnThisOn !== null ? \Arr::contains($returnThisOn, $method) : false))
                $result = $this;

            return $result;
        }
    }
}

?>