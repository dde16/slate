<?php

namespace Slate\Http {

    use Closure;
    use Slate\IO\StreamReader;
    use Slate\Data\Collection;
    use Slate\Data\FieldPrepped;
    use Slate\Facade\Security;
    use Slate\Media\Uri;
    use Slate\Neat\Attribute\Getter;
    use Slate\Neat\Attribute\Setter;

class HttpRequest extends HttpPacket {
        protected string     $path;
        protected int        $method;
        protected float      $version;
        
        protected $route = null;
        protected ?Collection $parameters = null;

        protected ?StreamReader $bodyStream = null;

        protected Collection $query;

        protected Uri $uri;

        public function __construct(
            int $method,

            Uri|string $uri,

            float $version = 1.1,
            
            array $headers = [],
            array $cookies = [],
            
            array $query   = [],
            array $files   = []
        ) {
            parent::__construct();

            $this->uri      = is_string($uri) ? (new Uri($uri)) : $uri;
            $this->method   = $method;
            $this->version  = $version;

            $this->query   = new Collection($query, Collection::READABLE);
            
            $this->headers = new Collection($headers, Collection::READABLE);
            $this->cookies = new Collection($cookies, Collection::READABLE);
            $this->files   = new Collection($files, Collection::READABLE);
        }

        #[Getter("uri")]
        public function getUri(): Uri {
            return $this->uri;
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
                $this->parameters, $this->uri->query, $this->query
            ], $key, $options, multisource: true);
        }

        public function gets(array $schema): array {
            return \Arr::gets([
                $this->parameters, $this->uri->query, $this->query
            ], $schema, multisource: true);
        }

        public function var(string $name): mixed {
            return (new FieldPrepped($name))->from($this->parameters, $this->uri->query, $this->query);
        }

        #[Getter("body")]
        public function getBody(): StreamReader {
            if(!$this->bodyStream)
                $this->bodyStream = new StreamReader(fopen("php://input", "r"));

            return $this->bodyStream;
        }

        public function __clone() {
            $this->uri = clone $this->uri;
        }

        public static function capture(): static {
            $uri = new Uri();
            $uri->scheme = \Str::lower(HttpEnvironment::getProtocol());
            $uri->host     = $_SERVER["HTTP_HOST"];

            $queries = ["get" => $_GET, "post" => $_POST];

            foreach($queries as $method => $query) {
                if(env("mvc.security.auto-sanitise", ["fallback" => true]) === true)
                    $queries[$method] = Security::sanitise(
                        $query,
                        env("mvc.security.auto-sanitise.escapes", ["fallback" => ["\"", "'", "`"], "validator" => Closure::fromCallable('is_array')])
                    );

                
            }

            $uri->setPath(HttpEnvironment::getPath());
            $uri->query = $queries["get"];
            
            /** Load Version */
            $version = HttpEnvironment::getVersion();

            /** Load Method */
            $method = HttpMethod::getValue(\Str::uppercase(HttpEnvironment::getMethod()));

            /** Load Headers */
            $headers = HttpEnvironment::getHeaders();

            /** Load Cookies */
            $cookies = HttpEnvironment::getCookies();

            /** Load Query */
            $query = $queries["post"];

            /** Load Files */
            $files = HttpEnvironment::getFiles();

            
            return(new static($method, $uri, $version, $headers, $cookies, $query, $files));
        }
    }
}

?>