<?php declare(strict_types = 1);

namespace Slate\Utility {
    use Closure;
    use DateTime;
    use Slate\IO\Contract\IStreamWriteable;
    use Throwable;

    class Log {
        use TMacroable;

        const OFF      = 0;

        /**
         * Logs that are used for interactive investigation during development.
         * These logs should primarily contain information useful for debugging
         * and have no long-term value.
         */
        const DEBUG    = (1<<0);

        /**
         * Logs that track the general flow of the application. These logs should
         * have long-term value.
         */
        const INFO     = (1<<1);

        /**
         * Logs that highlight an abnormal or unexpected event in the application flow,
         * but do not otherwise cause the application execution to stop.
         */
        const WARN     = (1<<2);

        /**
         * Highlight when the current flow of execution is stopped due
         * to a failure. These should indicate a failure in the current activity,
         * not an application-wide failure.
         */
        const ERROR    = (1<<3);

        /**
         * Logs that describe an unrecoverable application or system crash,
         * or a catastrophic failure that requires immediate attention.
         */
        const CRITICAL = (1<<4);

        /**
         * A flag on whether logs should be verbose.
         * NOT A LOG LEVEL
         */
        const VERBOSE = (1<<5);

        const ALL      = 
            Log::DEBUG |
            Log::INFO  |
            Log::WARN  |
            Log::ERROR | 
            Log::CRITICAL;

        const NAMES = [
            Log::DEBUG => "DEBUG",
            Log::INFO => "INFO",
            Log::WARN => "WARN",
            Log::ERROR => "ERROR",
            Log::CRITICAL => "CRITICAL"
        ];

        protected array $streams;

        protected array  $context;
        protected array  $formatter;
        protected int    $level;

        /**
         * Log::__construct
         *
         * @param array $streams   Any streams you want to output your log line to
         * @param array $formatter An effective tuple that takes a master/fallback message as its first element and as the second, a key value pair for messages specific to the log level.
         * @param array $context   Any context that can be static (scalar values) or dynamic (closures) that will be formatted onto the format message.
         * @param int   $level     The level that the logger will output at.
         */
        public function __construct(
            array $streams,
            array $formatter = [
                "[{datetime} {level}] {message}",
                []
            ],
            array $context = [],
            int $level = Log::ALL
        ) {
            $this->streams = $streams;

            if(count(($nonWriteableStreams = \Arr::filter($streams, function($stream) {
                return !\Cls::hasInterface($stream, IStreamWriteable::class);
            }))) > 0) {
                throw new \Error("Non IStreamWriteable streams detected in constructor.");
            }

            $this->setContext(\Arr::merge(
                [
                    "datetime" => function() {
                        return(new DateTime("now"))->format("D d M Y H:i:s.v");
                    }
                ],
                $context
            ));

            $this->setFormatter($formatter);
            $this->setLevel($level);
        }

        public function setLevel(int $level): void {
            $this->level = $level;
        }

        public function getContext(bool $eval = true): array {
            return $eval ? \Arr::map(
                $this->context,
                function($contextualiser) {
                    if($contextualiser instanceof Closure)
                        $contextualiser = $contextualiser();

                    return $contextualiser;
                }
            ) : $this->context;
        }

        public function addContext(array $context): void {
            $this->context = \Arr::merge(
                $this->context,
                $context
            );
        }

        public function setFormatter(array $formatter): void {
            $this->formatter = $formatter;
        }

        public function setContext(array $context): void {
            $this->context = $context;
        }

        public function format(int $loglevel, mixed $object): string {
            $formatter = $this->formatter[1][$loglevel] ?: $this->formatter[0];
            $context   = \Arr::merge(
                $this->getContext(eval: true),
                ["message" => $object, "level" => static::NAMES[$loglevel]]
            );

            if($formatter instanceof Closure)
                $formatter = $formatter($object);

            return \Str::format($formatter, $context);
        }

        public function err(mixed $object): void {
            $this->log(Log::ERROR, $object);
        }

        public function throw(Throwable $throwable, int $level = Log::CRITICAL): void {
            $tb = "    ";
            $this->log(
                $level,
                "\n".get_class($throwable).": ".
                "\n{$tb}Message : ".\Str::wrap($throwable->getMessage(), "'").
                "\n{$tb}File    : ".\Str::wrap($throwable->getFile(), "'").
                "\n{$tb}Line    : ".\Str::wrap($throwable->getLine(), "'").
                "\n{$tb}Traceback".
                "\n".\Arr::join(
                    \Arr::map(
                        \Str::split(
                            $throwable->getTraceAsString(),
                            "\n"
                        ),
                        function($v) use($tb) {
                            return "{$tb}{$tb}{$v}";
                        }
                    ),
                    "\n"
                )."\n"
            );
        }

        public function debug(mixed $object): void {
            $this->log(Log::DEBUG, $object);
        }

        public function info(mixed $object): void {
            $this->log(Log::INFO, $object);
        }

        public function warn(mixed $object): void {
            $this->log(Log::WARN, $object);
        }

        public function crit(mixed $object): void {
            $this->log(Log::CRITICAL, $object);
        }

        public function log(int $level, mixed $object): void {
            if(\Integer::hasBits($this->level, $level)) {
                $format = $this->format($level, $object);

                foreach($this->streams as $stream)
                    $stream->write("$format\n");
            }
        }
    }
}

?>