<?php

namespace Slate\IO {
    use Slate\Utility\Logger;

    class LoggerFile extends Logger {
        public function __construct(
            string $path,
            array $formatter = [
                "[{datetime} {level}] {message}",
                []
            ],
            array $context = [],
            int $level = Logger::ALL,
            array $streams = []
        ) { 
            parent::__construct(
                [new File($path), ...\Arr::values($streams)],
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