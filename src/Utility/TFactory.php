<?php declare(strict_types = 1);

namespace Slate\Utility {
    trait TFactory {
        public static function factory(string $differentiator, array $arguments = []) {
            if(($factory = \Cls::getConstant(static::class, "FACTORY")) !== null) {
                if(is_string($factory)) {
                    return $factory::create($differentiator, $arguments);
                }
                else {
                    throw new \Error(\Str::format(
                        "'{}::FACTORY' must be a string.",
                        static::class
                    ));
                }
            }
            else {
                throw new \Error(\Str::format(
                    "'{}' does not have the 'FACTORY' string, class, constant.",
                    static::class
                ));
            }
        }
    }
}

?>