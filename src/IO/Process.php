<?php declare(strict_types = 1);

namespace Slate\IO {
    class Process {
        public const STDIN  = 0;
        public const STDOUT = 1;
        public const STDERR = 2;

        const READ = "r";
        const WRITE = "w";

        public $resource;
        public string $command;
        public string $cwd;

        /**
         * Descriptor examples
         * 
         * Write to pipe
         * ["pipe", "w"]
         * 
         * Write to file
         * ["file", "/tmp/error.txt"]
         */
        public array $descriptors = [
            0 => ["pipe", "r"], // stdin  is a pipe that the child will read from
            1 => ["pipe", "w"], // stdout is a pipe that the child will write to
            2 => ["pipe", "w"]  // stderr is a pipe that the child will write to
        ];

        public $stdin;
        public $stdout;
        public $stderr;

        public function __construct(string $cwd = null) {
            $this->cwd = $cwd;
        }

        public function pipe(int $pipe, string $mode): void {
            $this->descriptors[$pipe][0] = "pipe";
            $this->descriptors[$pipe][1] = $mode;
        }

        public function file(int $pipe, string $path): void {
            $this->descriptors[$pipe][0] = "file";
            $this->descriptors[$pipe][1] = $path;
        }

        public function open(string $command, string ...$arguments): void {
            $pipes = [];

            $arguments = array_merge([$command], $arguments);
            $command   = \Arr::join($arguments, " ");

            $this->resource = proc_open(
                $command,
                $this->descriptors,
                $pipes,
                $this->cwd
            );

            list($stdin, $stdout, $stderr) = $pipes;

            $this->stdin  = $this->descriptors[0][0] === "file"
                ? new File($this->descriptors[0][1] === "r" ? "w" : "r")
                : Stream::factory($this->descriptors[0][1]  === "r" ? "w" : "r", [ &$stdin ]);

            $this->stdout  = $this->descriptors[1][0] === "file"
                ? new File($this->descriptors[1][1] === "r" ? "w" : "r")
                : Stream::factory($this->descriptors[1][1]  === "r" ? "w" : "r", [ &$stdout ]);

            $this->stderr  = $this->descriptors[2][0] === "file"
                ? new File($this->descriptors[2][1] === "r" ? "w" : "r")
                : Stream::factory($this->descriptors[2][1]  === "r" ? "w" : "r", [ &$stderr ]);

        }

        public function close(): int {
            $this->stdin->close();
            $this->stdout->close();
            $this->stderr->close();
            
            $status = proc_close($this->resource);

            $this->stdin = null;
            $this->stdout = null;
            $this->stdoerr = null;

            return $status;
        }
    }
}

?>