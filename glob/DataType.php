<?php


abstract class DataType extends \Slate\Utility\Facade {
    /**
     * Validate that the value is of the class type.
     * 
     * @throws \Error
     * 
     * @param mixed $value
     * 
     * @return bool
     */
    public static function validate($value): bool {
        $validator = \Cls::getConstant(static::class, "VALIDATOR");

        if($validator === NULL)
            throw new Error("A validator was not found for '" . static::NAMES[0] . "'.");

        return \Fnc::call($validator, [$value]);
    }
}

?>