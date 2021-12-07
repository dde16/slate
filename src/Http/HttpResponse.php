<?php

namespace Slate\Http {

    use Slate\Data\Collection;
    use Slate\IO\StreamWriter;
    use Slate\Http\HttpCode;
    use Slate\Neat\Attribute\Getter;

    class HttpResponse extends HttpPacket {
        public int           $status     = HttpCode::OK;
        protected ?StreamWriter $bodyStream = null;
        public array         $timings    = [];

        public function __construct() {
            parent::__construct();
            
            $this->headers = new Collection();
            $this->cookies = new Collection();
            $this->files   = new Collection();
        }

        #[Getter("body")]
        public function getBody(): StreamWriter {
            if(!$this->bodyStream)
                $this->bodyStream = new StreamWriter(fopen("php://output", "a+"));

            return $this->bodyStream;
        }
        
        /**
         * Load a single file into the request.
         *
         * @return void
         */
        public function sendFile(): void {
            $file = $this->files->first();

            if(!$file->isOpen("r")) $file->open("r");

            $file->pipe($this->body);

            $this->headers["Content-Type"] = $file->httpMime;
            $this->headers["Content-Disposition"] = \Str::format(
                "attachment; name=\"{basename}\"; filename=\"{filename}\"",
                [ "basename" => basename($file->httpField), "filename" => $file->httpField, ]
            );
            $this->headers["X-Suggested-Filename"] = $file->httpField;
            $file->close();
        }

        /**
         * Load multiple files into the response.
         * 
         * @return void
         */
        public function sendFiles(): void {
            if(!$this->files->isEmpty()) {
                $filesBoundary = \Hex::encode(openssl_random_pseudo_bytes(16));

                foreach($this->files as $file) {
                    $this->body->write(\Str::format(
                        "--{boundary}\r\nContent-Disposition: attachment; name=\"{basename}\"; filename=\"{filename}\"\r\nContent-Type: {contentType}\r\n\r\n",
                        [
                            "boundary" => $filesBoundary,
                            "basename" => basename($file->httpField),
                            "filename" => $file->httpField,
                            "contentType"  => $file->httpMime
                        ]
                    ));

                    $file->pipe($this->body);
                    $this->body->write("\r\n\r\n");
                    $file->close();
                }

                $this->body->write("--".$filesBoundary."--");
            }
        }

        public function elapsed(string $name): void {
            $elapsed = $delta = HttpEnvironment::getElapsedTime();

            if(count($this->timings) > 0) {
                $delta = $elapsed - end($this->timings)[1];
            }

            $this->timings[] = [$name, $elapsed, $delta];
        }

        public function send(): void {
            
            http_response_code($this->status);

            if(!$this->files->isEmpty()) {
                if($this->files->count() === 1) {
                    $this->sendFile();
                }
                else {
                    $this->sendFiles();
                }
            }

            $this->timings[] = ["total", HttpEnvironment::getElapsedTime(), HttpEnvironment::getElapsedTime()];

            $timings = \Str::join(
                \Arr::map(
                    $this->timings,
                    function($timing) {
                        return $timing[0].";dur=" . $timing[2];
                    }
                ),
                ", "
            );

            $this->headers["Server-Timing"] = $timings;
            
            foreach($this->headers as $key => $value) 
                header("$key: $value", true);

            foreach ($this->cookies as $key => $arguments) 
                \Fnc::call('setcookie', $arguments);
        }
    }
}

?>