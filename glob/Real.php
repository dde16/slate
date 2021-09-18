<?php

class Real extends ScalarType implements \Slate\Data\ISizeStaticallyAttainable {
    const NAMES            = ["float", "double", "real"];
    const GROUP            = ScalarType::class;
    const VALIDATOR        = "is_float";
    const CONVERTER        = "floatval";

    const MIN              = PHP_FLOAT_MIN;
    const MAX              = PHP_FLOAT_MAX;

    public static function getSize(): int {
        return strlen(\Any::dec2bin(PHP_FLOAT_MAX));
    }

    public static function dynamicRound(int|float $number, int|float $rounder): int|float {
        $rounderDecimalPow    = pow(10, (\Real::getDecimalDigits($rounder) - 1));
        $numberDecimal = fmod(($number * $rounderDecimalPow), 1.0);
        $numberLowered = $number - $numberDecimal;
    
        return $numberLowered + (($numberDecimal >= $rounder) ? (1 / $rounderDecimalPow) : 0);  
    }

    public static function fromDateTimeSpan(\DateTimeInterface|\DateInterval|int|float $datetime): float {
        $timespan = \Real::fromDateTime($datetime);

        return microtime(true) + $timespan;
    }
    
    public static function fromDateTime(\DateTimeInterface|\DateInterval|int|float $datetime): float {
        if(is_object($datetime)) {
            if(\Cls::hasInterface($datetime, \DateTimeInterface::class)) {
                return $datetime->getTimestamp() + (\Real::tryparse($datetime->format('u')) / (10**6));
            }
            else if(\Cls::isSubclassInstanceOf($datetime, \DateInterval::class)) {
                return 
                    ($datetime->f)
                    + ($datetime->s)
                    + ($datetime->i * 60)
                    + ($datetime->h * 60 * 60)
                    + ($datetime->d * 60 * 60 * 24)
                    + ($datetime->m * 60 * 60 * 24 * 30)
                    + ($datetime->y * 60 * 60 * 24 * 365);
            }
        }
        else if(!is_float($datetime)) {
            $datetime = \Real::tryparse($datetime);
        }

        if($datetime < 0)
            throw new \Error("Timestamp must be a positive float.");

        return $datetime;
    }

    public static function normalise(float|int $number): array {
        $shift = 0;

        $number = (float)$number;

        $shift = 0;
    
        while($number < 1) {
            $number *= 10;
            $shift++;
        }
    
        return [$number, $shift];
    }

    public static function getDecimalDigits(float|int $number): int {
        return \Real::normalise($number)[1];
    }

    public static function fromPercentage(string|int|float $percent): float {
        $type = \Any::getType($percent, tokenise: true);

        switch($type) {
            case \Type::STRING:
                if(preg_match(\Str::wrapc(\Str::PERCENTAGE_PATTERN, "/^$/"), $percent, $matches)) {
                    return \Real::tryparse($matches["number"]);
                }
                else {
                    throw new \Slate\Exception\ParseException("Unable to parse percentage string, incorrect format.");
                }
                break;
            case \Type::INTEGER:
                return $percent / 100;
                break;
            case \Type::FLOAT:
                return $percent;
                break;
        }
    }
}

?>