<?php

namespace Slate\Sql {

use Slate\Exception\UndefinedRoutineException;

trait TSqlModifierMiddleware {
        public function __call(string $name, array $arguments): mixed {
            if(\Arr::hasKey(SqlModifier::TOGGLERS, $name)) {
                $toggler = SqlModifier::TOGGLERS[$name];

                if(is_array($toggler)) {
                    $modifier = $toggler[0];
                    $arguments = [$toggler[1]];
                }
                else {
                    $modifier = $toggler;
                }

                return $this->setModifier($modifier, ...$arguments);
            }
    
            throw new UndefinedRoutineException([static::class, $name], UndefinedRoutineException::ERROR_UNDEFINED_METHOD);
        }
    }
}

?>