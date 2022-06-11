<?php declare(strict_types = 1);

namespace Slate\Utility {
    use Closure;
    use DateTime;

    class Job {
        public const SECONDS  = (1<<0) | Job::UNIT_TIME;
        public const MINUTES  = (1<<1) | Job::UNIT_TIME;
        public const HOURS    = (1<<2) | Job::UNIT_TIME;
        public const DAYS     = (1<<3) | Job::UNIT_DATE;
        public const MONTHS   = (1<<4) | Job::UNIT_DATE;
        public const YEARS    = (1<<5) | Job::UNIT_DATE;

        public const UNITS_ORDER = [Job::SECONDS => Job::SECONDS, Job::MINUTES => Job::MINUTES, Job::HOURS => Job::HOURS, Job::DAYS => Job::DAYS, Job::MONTHS => Job::MONTHS, Job::YEARS => Job::YEARS];
        // public const UNITS_ORDER = ["SECONDS", "MINUTES", "HOURS", "DAYS", "MONTHS", "YEARS"];
        public const UNITS= [
            Job::SECONDS => [
                "accessor"  => "s",
                "max"       => 60,
                "next"      => Job::MINUTES,
                "default"   => 0,
                "seconds"   => 1,
            ],
            Job::MINUTES => [
                "accessor"  => "i",
                "converter" => "castMinutesToSeconds",
                "max"       => 60,
                "default"   => 0,
                "next"      => Job::HOURS,
                "seconds"   => 60
            ],
            Job::HOURS => [
                "accessor" => "H",
                "converter" => "castHoursToSeconds",
                "max"      => 24,
                "default"   => 1,
                "next"      => Job::DAYS,
                "seconds"   => 60*60
            ],
            Job::DAYS => [
                "accessor" => "d",
                "default"   => 1,
                "seconds"   => 60*60*24
            ],
            Job::MONTHS => [
                "accessor"  => "m",
                "default"   => 1,
                "converter" => "castMonthsToSeconds"
            ]
        ];

        public const UNIT_TIME = (1<<6);
        public const UNIT_DATE = (1<<7);

        protected Closure $closure;
        protected array $arguments;

        protected array $timings;

        protected int   $backoff;

        public function __construct(
            array $timings = [],
            int $backoff = -1
        ) {

            // return ((($year % 4) == 0) && ((($year % 100) != 0) || (($year % 400) == 0)));

            $this->timings = 
                \Arr::rearrange($timings, static::UNITS_ORDER, function() {
                    return null;
                })
            ;

            $this->backoff = $backoff;

            $imbalanced = true;

            while($imbalanced) {
                $imbalanced = false;

                foreach(static::UNITS as $unitKey => $unit) {
                    $unitValue = $this->timings[$unitKey];

                    if($unitValue) {
    
                        if($unit["max"]) {
                            list($rational, $remainder) = \Math::divmod($unitValue, $unit["max"]);
    
                            if($rational >= 1) {
                                $this->timings[$unitKey] = $remainder;
                                $this->timings[$unit["next"]] += $rational;
                                
                                if($unitKey !== Job::MONTHS) {
                                    $imbalanced = true;
                                }
                                else {
                                    throw new \Error("Cannot go over a year.");
                                }
                            }
                        }
                    }
                }
            }
        }

        public function getWait(): int {
            $now   = new DateTime();
            $epoch = new DateTime();
            $epoch->setDate(1970, 1, 1);
            $epoch->setTime(1, 0, 0, 0);

            foreach($this->timings as $unitKey => $unitValue) {
                if($unitValue !== null)
                    if($unitValue > 0)
                        $largestUnit = $unitKey;
            }

            if($largestUnit === null)
                throw new \Error();

            $largestUnitNext = static::UNITS[$largestUnit]["next"];

            $closure = function($unit)  use($now)  {
                return ($this->timings[$unit] ?  $this->timings[$unit] : static::UNITS[$unit]["default"]);
            };

            $date = [1970, ...\Arr::map(
                [Job::MONTHS, Job::DAYS],
                $closure
            )];

            $time = \Arr::map(
                [Job::HOURS, Job::MINUTES, Job::SECONDS],
                $closure
            );

            $offset = clone $epoch;
            $offset->setDate(
                ...$date
            );

            $offset->setTime(
                ...$time
            );

            $offset->setTimestamp(
                \Math::jump($now->getTimestamp(), $offset->getTimestamp())
            );

            $largestUnitNextValue = $offset->format(static::UNITS[$largestUnitNext]["accessor"]) * static::UNITS[$largestUnitNext]["seconds"];

            $largestNextMod = \Math::mod(
                ($largestUnitNextValue)
                    + ($offset->format(static::UNITS[$largestUnit]["accessor"]) * static::UNITS[$largestUnit]["seconds"]),
                $largestUnitNextValue
            );


            if($largestNextMod == 0 && $this->timings[$largestUnitNext] === 0)
                $offset->setTimestamp(
                    $offset->getTimestamp() + $this->timings[$largestUnit] * static::UNITS[$largestUnit]["seconds"]);
            
            return \Integer::fromDateTime($now->diff($offset));
        }
    
        public function go(Closure $closure, array $arguments = []): void {
            $executions = 0;

            while($this->backoff !== -1 ? $executions < $this->backoff : true) {
                $wait = $this->getWait();

                if($wait === 0) {
                    sleep(2);
                    $wait = $this->getWait();
                }
                
                sleep($wait);

                ($closure)(...$arguments);
            }
        
        }
    }
}

?>