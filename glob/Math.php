<?php declare(strict_types = 1);
abstract class Math {
    public const EXCLUSIVE = 1;
    public const INCLUSIVE = 2;

    public const ROUND_HALF_DOWN = PHP_ROUND_HALF_DOWN;
    public const ROUND_HALF_EVEN = PHP_ROUND_HALF_EVEN;
    public const ROUND_HALF_ODD  = PHP_ROUND_HALF_ODD;
    public const ROUND_HALF_UP   = PHP_ROUND_HALF_UP;

    public const E_ANTI_CLOCKWISE = -1;
    public const E_CLOCKWISE      = 1;

    public static function product(array $set): array {
        if(!$set)
            return [[]];
    
        $subset = array_shift($set);
        $cartesianSubset = \Math::product($set);
    
        $result = [];
        
        foreach($subset as $value) {
            foreach($cartesianSubset as $cartesianValue) {
                array_unshift($cartesianValue, $value);
                $result[] = $cartesianValue;
            }
        }
    
        return $result;
    }

    /**
     * Find the interior angle of a regular polygon.
     * 
     * @param int $vertices
     * 
     * @return float|int
     */
    public static function interiorAngleRegularPolygon(int $vertices): float|int {
        return 180 - \Math::exteriorAngleRegularPolygon($vertices);
    }
    
    /**
     * Find the exterior angle of regular polygon.
     *
     * @param  mixed $vertices
     * @return float
     */
    public static function exteriorAngleRegularPolygon(int $vertices): float|int {
        return ((($vertices-2)*180)/$vertices);
    }

    //TODO: refract into dedicated units
    public static function latlongAddDistance($latitude, $longitude, $distance, $measurement, $bearing) {
        $scales = [
            [
                "names" => [
                    "km",
                    "kilometres",
                    "kilometers"
                ],
                "convert" => function($d) {
                    return $d;
                }
            ],
            [
                "names" => [
                    "m",
                    "metres",
                    "meters"
                ],
                "convert" => function($d) {
                    return $d / 1000;
                }
            ],
            [
                "names" => [
                    "cm",
                    "centimetres",
                    "centimeters"
                ],
                "convert" => function($d) {
                    return $d / 100000;
                }
            ],
            [
                "names" => [
                    "mm",
                    "milimetres",
                    "milimeters"
                ],
                "convert" => function($d) {
                    return $d / 1000000;
                }
            ],
            [
                "names" => [
                    "in",
                    "inches",
                    "\""
                ],
                "convert" => function($d) {
                    return $d / 39370.079;
                }
            ],
            [
                "names" => [
                    "ft",
                    "foot",
                    "feet",
                    "'"
                ],
                "convert" => function($d) {
                    return $d * 1.609;
                }
            ]
        ];
    
        $km = 0;
    
        foreach($scales as $scale) {
            foreach($scale["names"] as $name) {
                if($name == $measurement) {
                    $km = $scale["convert"]($distance);
                }
            }
        }
    
        $distance = $km;
        $earthRadius = 6371;
        $lat1 = deg2rad($latitude);
        $lon1 = deg2rad($longitude);
        $bearing = deg2rad($bearing);
    
        $lat2 = asin(sin($lat1) * cos($distance / $earthRadius) + cos($lat1) * sin($distance / $earthRadius) * cos($bearing));
        $lon2 = $lon1 + atan2(sin($bearing) * sin($distance / $earthRadius) * cos($lat1), cos($distance / $earthRadius) - sin($lat1) * sin($lat2));
    
        return [rad2deg($lat2), rad2deg($lon2)];
    }

    /**
     * Get the dimensions of a matrix.
     * 
     * @param array $matrix
     * 
     * @return int
     */
    public static function getDimensions(array $matrix): int {
        return max(\Arr::map($matrix, 'count'));
    }

    /**
     * Get the euclidean distance of two matrices.
     * 
     * @param array $first
     * @param array $second
     * 
     * @return float
     */
    public static function euclideanDistance(array $first, array $second): float {
        $sum = 0;

        $dimensions = max([count($first), count($second)]);

        $first = \Arr::padRight($first, 0, $dimensions);
        $second = \Arr::padRight($second, 0, $dimensions);

        for($dimension = 0; $dimension < $dimensions; $dimension++) {
            $sum += ($second[$dimension] - $first[$dimension]) ** 2;
        }

        return sqrt($sum);
    }

    /**
     * Perform eulers formula.
     * 
     * @param float|int $i
     * @param float|int $frequency
     * @param float|int $time
     * @param float|int $directory
     * 
     * @return float
     */
    public static function eulersFormula(float|int $i, float|int $frequency = 1, float|int $time = 1, int $direction = \Math::E_CLOCKWISE): float {
        return exp($direction  * 2 * pi() * $i * $frequency * $time);
    }

    /**
     * Finds the peaks of a given 1D dataset.
     * 
     * @param array $array 
     * 
     * @return array
     */
    public static function peaks(array $array): array {
        $last    = 0;
        $peaks   = [];
        $upwards = false;

        foreach($array as $value) {
            if($value > $last) {
                $upwards = true;
            }
            else if($value < $last) {
                if($upwards)
                    $peaks[] = $last;

                $upwards = false;
            }

            $last = $value;
        }

        return $peaks;
    }


    /**
     * Calculate the standard deviation.
     * 
     * @param array $array 1D dataset
     * @param bool  $sample
     * 
     * @return float
     */
    public static function stdev(array $array, bool $sample = false): float {
        return sqrt(static::variance($array, $sample));
    }

    /**
     * Get the mean average.
     * 
     * @param array $array
     * 
     * @return float
     */
    public static function mean(array $array): float {
        if (empty($array))
            return 0;

        $sum = array_sum($array);

        if ($sum === 0)
            return 0;

        $count = count($array);

        return $sum / $count;
    }

    /**
     * Get the variance of a 1d dataset
     * 
     * @param array $array
     * @param bool  $sample
     * 
     * @return int|float
     */
    public static function variance(array $array, bool $sample = false): int|float {
        $size = count($array);
        $average = \Math::mean($array);
    
        return array_sum(
            \Arr::map(
                $array,
                function($number)  use(&$average) {
                    return pow(($number - $average), 2);
                }
            )
        ) / ($size - ($sample ? 1 : 0));
    }
    
    /**
     * Get the zScores for a given 1d dataset.
     *
     * @param  mixed $array
     * @return void
     */
    public static function zScores(array $array): array {
        $size = count($array);

        if($size === 1)
            return [1.0];
        else if($size === 0)
            return [];

        $mean = array_sum($array) / $size;
        $standardDeviation = \Math::stdev($array);
    
        return \Arr::map(
            $array, 
            function($number) use($standardDeviation, $mean) {
                return ($number - $mean) / $standardDeviation;
            }
        );
    }

    /**
     * Division which provides the remainder as rational number.
     * 
     * @param int|float $number
     * @param int|float $divisor
     * 
     * @return array
     */
    public static function divmod(int|float $number, int|float $divisor): array {
        list($rational, $irrational) = \Math::div($number, $divisor);
        $remainder = $irrational * $divisor;

        return [$rational, (is_int($divisor) ? round($remainder) : $remainder)];
    }

    /**
     * Division which provides the remainder as an irrational number.
     * 
     * @param int|float $number
     * @param int|float $divisor
     * 
     * @return array
     */
    public static function div(int|float $number, int|float $divisor): array {
        $division   = (float)$number / (float)$divisor;
        $irrational = fmod($division, 1.0);
        $rational   = (int)$division;

        return [$rational, $irrational];
    }

    /**
     * Rounding using whole numbers.
     * 
     * @param int|float $number
     * @param int|float $anchor
     * 
     * @return float
     */
    public static function nearest(int|float $number, int|float $anchor): float {
        return round($number / $anchor) * $anchor;
    }    

    /**
     * Calculate the Binomial Coefficient.
     * 
     * @param int $n 
     * @param int $k
     * 
     * @return float
     */
    public static function binomialCoefficient(int $n, int $k): float {
        return \Math::factorial($n) / (\Math::factorial($k) * \Math::factorial($n - $k));
    }

    /**
     * Round down (floor) to using whole numbers.
     * 
     * @param float|int $number
     * @param float|int $anchor
     * 
     * @return int|float
     */
    public static function fall(int|float $number, int|float $anchor): int|float {
        return ((int)($number / $anchor)) * $anchor;
    }

    /**
     * Round up (ceil) to using whole numbers.
     * 
     * @param float|int $number
     * @param float|int $anchor
     * 
     * @return int|float
     */
    public static function jump(int|float $number, int|float $anchor): int|float {
        $division = $number / $anchor;

        return (\Math::mod($division, 1.0) !== 0.0 ? ceil($division) : $division) * $anchor;
    }

    /**
     * Get the factorial of a number.
     * 
     * @param int $number
     * 
     * @return int
     */
    public static function factorial(int $number): int {
        for($factorial = 2; $number-1 > 1; $factorial *= $number--);
        return $factorial;
    }

    /**
     * An all inclusive modulus function.
     * 
     * @param int|float $value
     * @param int|float $modulo
     * 
     * @return int|float
     */
    public static function mod(int|float $value, int|float $modulo): int|float {
        if(is_float($value) && is_int($modulo)) {
            $modulo = (float)$modulo;

            $function =  'fmod';
        }
        else if(is_int($value) && is_float($modulo)) {
            $value = (float)$value;

            $function = 'fmod';
        }
        else if(is_int($value) && is_int($modulo)) {
            $function = function ($value, $modulo) {
                return $modulo != 0 ? $value % $modulo : $value;
            };
        }
        else if(is_float($value) && is_float($modulo)) {
            $function = 'fmod';
        }
        else {
            throw new \BadFunctionCallException();
        }

        return $function($value, $modulo);
    }

    /**
     * Inverse modulo.
     * 
     * @param int|float $value
     * @param int|float $modulo
     * 
     * @return int|float
     */
    public static function imod(int|float $value, int|float $modulo): int|float {
        $value = \Math::mod($value, $modulo);

        for($i = 1; $i < $modulo; $i++) {
            if(\Math::mod(($value * $i), $modulo) == 1) {
                return $value;
            }
        }

        return 1;
    }

    /**
     * Normalise a 1d dataset.
     * 
     * @param array $numbers
     * 
     * @return array
     */
    public static function normalise(array $numbers, float $low = 0.0, float $high = 1.0): array {
        $minimum = \Arr::min($numbers) * (1 - $low);
        $maximum = \Arr::max($numbers) * ((1 - $high) + 1);
        $range   = $maximum - $minimum;

        return \Arr::map($numbers, function($number) use($minimum, $range) {
            return ($number - $minimum) / $range;
        });
    }
    
    /**
     * Rounds a whole number to a given bounds.
     */
    public static function swing($value, array $boundary, int $mode = PHP_ROUND_HALF_UP): float|int {
        list($minimum, $maximum) = $boundary;

        list($normalValue) = \Math::normalise([$value, $minimum, $maximum]);

        return (round($normalValue, 0, $mode) == 0) ? $minimum : $maximum;
    }

    /**
     * Check if a value is within a set of bounds.
     *
     * @param float|int $minimum
     * @param float|int $maximum
     * @param float|int $value
     * @return float:int
     */
    //TODO: remove
    public static function between($value, array $boundary, int $mode = \Math::EXCLUSIVE) {
        list($minimum, $maximum) = $boundary;

        switch ($mode) {
            case \Math::EXCLUSIVE: // Default
                return (($value > $minimum) && ($value < $maximum));
                break;
            case \Math::INCLUSIVE:
                return (($value >= $minimum) && ($value <= $maximum));
                break;
        }

        return false;
    }

    /**
     * Get the number of digits a number has.
     *
     * @param $number
     * @return float
     */
    public static function digits(int|float $number, int $base = 10): int {
        return ceil(log((float)$number, $base));
    }

    /**
     * Check if a value overflows boundaries of min/max, if so; wrap it around by how much its exceeds these bounaries.
     *
     * @param  int|float $value
     * @param  int|float $array    Min, max vector
     * @param  int|float $feedback Whether to return a value whether it did have to overflow.
     * 
     * @return int|float|array
     */
    public static function overflow(int|float $value, array $boundary, bool $feedback = false): int|float|array {
        list($minimum, $maximum) = $boundary;
        $overflowed = true;

        if($value > $maximum) {
            $value = ($minimum + (\Math::mod($value, $maximum))) - 1;
        }
        else if($value < $minimum) {
            $value = $value > 0 ? (($maximum - $value) + 1) : (($maximum + $value) - 1);
        }
        else {
            $overflowed = false;
        }

        return $feedback ? [$value, $overflowed] : $value;
    }

    public static function equalSet(array $array): bool {
        return \Math::subtract($array) == 0;
    }

    public static function equaliseMatrices(array ...$matrices): array {
        $size = \Arr::max(\Arr::map($matrices, Closure::fromCallable('count')));

        return \Arr::map($matrices, fn(array $array): array => \Arr::padRight($array, 0, $size));
    }

    public static function subtract(array $array): int|float {
        return \Arr::reduce(
            \Arr::lead($array),
            fn(int|null $prevLead, array $nextLead): int|float
                => ($nextLead[1] - $nextLead[0] - $prevLead),
            0
        );
    }

    public static function term(array $originalSet, int $limit = 3) {
        $exponent = 0;
    
        $deltaSet = $originalSet;
    
        do {
            $deltaSet = \Arr::map(
                \Arr::lead($deltaSet),
                fn(array $lead): int|float => $lead[1] - $lead[0]
            );
    
            $exponent++;
        } while($exponent <= $limit && !($equalised = (\Math::subtract($deltaSet) === 0)));
    
        if($equalised) {
            $product = $deltaSet[0];
            $comparativeSet = [];
    
            for($index = 0; $index < count($originalSet); $index++)
                $comparativeSet[] = $product * ($index + 1);
    
            $deltaSet = \Math::subtractMatrices($originalSet, $comparativeSet);
    
            if(\Math::equalSet($deltaSet)) {
                $expression = $product."n";
    
                if($exponent > 1)
                    $expression .= " ^ ".$exponent;
    
                if($deltaSet[0] > 0)
                    $expression .= " + " . $deltaSet[0];
    
                if($deltaSet[0] < 0)
                    $expression .= " - " . abs($deltaSet[0]);

                return $expression;
            }
    
        }
    
        return null;
    }


    public static function droop(array $array, callable $callback) {
        $floating = 0;
        $size     = count($array)-1;
        $last     = &$array[$size];
    
        for($index = 0; $index < $size; $index++) {
            $before = $array[$index];
            $after  = $callback($before);
            $delta  = $before - $after;
    
            $floating += $delta;
    
            $array[$index] = $after;
        }
    
        $last += $floating;
    
        return $array;
    }
    

    public static function distribute($number, $divisor) {
        $distribution = $number / $divisor;
        $decimal      = fmod((float)$distribution, 1.0);
        $remainder    = $divisor * $decimal;
        $integer      = (int)$distribution;
        
        return [...array_fill(0,  $integer, $divisor), $remainder];
    }

    public static function subtractMatrices(array ...$matrices) {
        $matrices = \Math::equaliseMatrices(...$matrices);

        $deltas = $matrices[0];
        $matrices = \Arr::slice($matrices, 1);

        foreach($matrices as $matrix)
            foreach($matrix as $index => $value) 
                $deltas[$index] -= $value;

        return $deltas;
    }

    public static function addMatrices(array ...$matrices) {
        $matrices = \Math::equaliseMatrices(...$matrices);

        $deltas = $matrices[0];
        $matrices = \Arr::slice($matrices, 1);

        foreach($matrices as $matrix)
            foreach($matrix as $index => $value) 
                $deltas[$index] += $value;

        return $deltas;
    }
}

?>