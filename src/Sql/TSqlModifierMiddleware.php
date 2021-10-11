<?php

namespace Slate\Sql {
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
    
            throw new \Error(\Str::format(
                "Call to undefined method {}::{}().",
                static::class, $name
            ));
        }
    }
}

?>