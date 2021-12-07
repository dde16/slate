<?php

namespace Slate\IO {

    use Closure;
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
            array $streams = [],
            // Closure $rotate = null
        ) { 
            $path = \Path::normalise($path);
            $file = new File($path);

            // if($rotate) {

            //     if($rotate($file)) {
            //         $index = -1;

            //         while(\Path::exists($freepath = ($path.(++$index))));

            //         $path = $freepath;
            //     }

            //     $file = new File($path);
            // }

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