<?php

namespace Slate\Mvc {
    interface IRateLimiter {
        /**
         * Clear the ratelimiter.
         *
         * @return void
         */
        function clear(): void;

        /**
         * Check whether a given IP has exceeded a given amount of attempts.
         *
         * @param string $ip
         * @param integer $maxAttempts
         *
         * @return boolean
         */
        function tooManyAttempts(string $ip, int $maxAttempts): bool;

        /**
         * Register a hit for an ip.
         *
         * @param string $key
         * @param int|float $decay Decay in seconds
         *
         * @return void
         */
        function hit(string $ip, int|float $decay = 60): void;

        /**
         * Get the number of attempts for a given ip.
         *
         * @param string $ip
         *
         * @return integer
         */
        function attempts(string $ip): int;

        /**
         * Get the number of attempts remaining for an ip.
         *
         * @param string $ip
         * @param integer $maxAttempts
         *
         * @return integer
         */
        function attemptsRemaining(string $ip, int $maxAttempts): int;
    }
}

?>