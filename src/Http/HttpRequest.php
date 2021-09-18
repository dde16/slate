<?php

namespace Slate\Http {
    use Slate\IO\StreamReader;
    use Slate\Data\Collection;
    use Slate\Neat\Attribute\Getter;
    use Slate\Neat\Attribute\Setter;

class HttpRequest extends HttpPacket {
        protected string     $path;
        protected int        $protocol;
        protected int        $method;
        protected float      $version;
        
        protected $route = null;
        protected ?Collection $parameters = null;

        protected ?StreamReader $bodyStream = null;

        protected Collection $query;

        public function __construct(
            int $protocol,
            int $method,
            
            string $path = "/",

            float $version = 1.1,
            
            array $headers = [],
            array $cookies = [],
            
            array $query   = [],
            array $files   = []
        ) {
            parent::__construct();

            $this->path     = $path;
            $this->protocol = $protocol;
            $this->method   = $method;
            $this->version  = $version;

            $this->query   = new Collection($query, Collection::READABLE);
            
            $this->headers = new Collection($headers, Collection::READABLE);
            $this->cookies = new Collection($cookies, Collection::READABLE);
            $this->files   = new Collection($files, Collection::READABLE);
        }

        #[Getter("path")]
        public function getPath(): string {
            return $this->path;
        }

        #[Getter("protocol")]
        public function getProtocol(): int {
            return $this->protocol;
        }

        #[Getter("method")]
        public function getMethod(): int {
            return $this->method;
        }

        #[Getter("version")]
        public function getVersion(): float {
            return $this->version;
        }

        #[Getter("parameters")]
        public function getParameters(): Collection {
            return $this->parameters ?: new Collection();
        }
        
        #[Setter("parameters")]
        public function setParameters(array $parameters): void {
            if($this->parameters !== null)
                throw new \Error("Unable to set the request parameters as it has already been set.");

            $this->parameters = new Collection($parameters, Collection::READABLE);
        }

        #[Getter("query")]
        public function getQuery(): Collection {
            return $this->query;
        }

        #[Setter("route")]
        public function setRoute($route): void {
            if($this->route !== null)
                throw new \Error("Unable to set the request route as it has already been set.");

            $this->route = $route;
        }

        #[Getter("route")]
        public function getRoute(): mixed {
            return $this->route;
        }

        public function get(string|int $key, array $options = []): mixed {
            return \Arr::get([
                $this->parameters, $this->query
            ], $key, $options, multisource: true);
        }

        public function gets(array $schema): array {
            return \Arr::gets([
                $this->parameters, $this->query
            ], $schema, multisource: true);
        }


        #[Getter("body")]
        public function getBody(): StreamReader {
            if(!$this->bodyStream)
                $this->bodyStream = new StreamReader(fopen("php://input", "r"));

            return $this->bodyStream;
        }

        public static function capture(): static {
            /** Load Protocol */
            $protocol = HttpProtocol::getValue(\Str::upper(HttpEnvironment::getProtocol()));

            /** Load Version */
            $version = HttpEnvironment::getVersion();

            /** Load Method */
            $method = HttpMethod::getValue(\Str::uppercase(HttpEnvironment::getMethod()));

            /** Load Headers */
            $headers = HttpEnvironment::getHeaders();

            /** Load Cookies */
            $cookies = HttpEnvironment::getCookies();

            /** Load Path */
            $path = HttpEnvironment::getPath();

            /** Load Query */
            $query = \Arr::merge($_GET, $_POST);

            /** Load Files */
            $files = HttpEnvironment::getFiles();

            return(new static($protocol, $method, $path, $version, $headers, $cookies, $query, $files));
        }
    }
}

?>