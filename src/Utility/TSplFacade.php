<?php declare(strict_types = 1);

namespace Slate\Utility {

use Slate\Exception\UndefinedRoutineException;

/**
     * A trait that adapts facades to be able to call Standard PHP library functions.
     */
    trait TSplFacade {        
        /**
         * @param  string $method
         * @param  array  $arguments
         * 
         * @throws Error  If the function passthrough doesn't exist.
         * 
         * @return mixed
         */
        public static function __callStatic(string $method, array $arguments): mixed {
            if(\Arr::hasKey(static::SPL, $method)) {
                $endpoint = static::SPL[$method];

                if(is_array($endpoint)) {
                    if($endpoint[0] === static::class) {
                        throw new \Error();
                    }
                }

                if(\Fnc::exists($endpoint)) {
                    return \call_user_func_array(
                        $endpoint,
                        $arguments
                    );
                }
                else {
                    throw new UndefinedRoutineException(
                        \Str::format(
                            "Call to undefined function {} from {}::{}()",
                            $endpoint,
                            static::class,
                            $method
                        )
                    );
                }
            }
            else {
                throw new UndefinedRoutineException([static::class, $method], UndefinedRoutineException::ERROR_UNDEFINED_METHOD);
            }
        }
    }
}

?>