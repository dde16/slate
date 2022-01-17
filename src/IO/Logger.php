<?php

namespace Slate\IO {

    use Closure;
    use Slate\Utility\Log;

    class Logger extends Log {
        public function __construct(
            string $path,
            array $formatter = [
                "[{datetime} {level}] {message}",
                []
            ],
            array $context = [],
            int $level = Logger::ALL,
            array $streams = [],
            string|FileRotator $rotator = null
        ) { 
            $path = \Path::normalise($path);
            $file = new File($path, rotator: $rotator ?? LoggerRotator::class);

            parent::__construct(
                [$file, ...\Arr::values($streams)],
                $formatter,
                $context,
                $level
            );

            $this->streams[0]->open("a+");
        }

        public function __destruct() {
            if($this->streams[0]->isOpen()) {
                $this->streams[0]->flush();
                $this->streams[0]->close();
            }
        }
    }
}

?>