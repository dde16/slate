<?php

namespace Slate\Utility {

    use Slate\Exception\UndefinedRoutineException;

    trait TPassthru {
        public function __call(string $method, array $arguments): mixed{ 
            $passthru = \Cls::getConstant(static::class, "PASSTHRU");
            $only = \Cls::getConstant(static::class, "PASSTHRU_METHODS", "*");
            $returnThisOn = \Cls::getConstant(static::class, "PASSTHRU_RETURN_THIS");

            if($only === "*" ? true : ($only !== null ? !\Arr::contains($only, $method) : false))
                throw new UndefinedRoutineException([static::class, $method], UndefinedRoutineException::ERROR_UNDEFINED_METHOD);

            $result = $this->{$passthru}->{$method}(...$arguments);

            
            if($returnThisOn === "*" ? true : ($returnThisOn !== null ? \Arr::contains($returnThisOn, $method) : false))
                $result = $this;

            return $result;

        }
    }
}

?>