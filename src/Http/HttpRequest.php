<?php declare(strict_types = 1);

namespace Slate\Http {

    use Closure;
    use Slate\IO\StreamReader;
    use Slate\Data\Collection;
    use Slate\Data\FieldPrepped;
    use Slate\Facade\Security;
    use Slate\Media\Uri;
    use Slate\Mvc\Route;
    use Slate\Neat\Attribute\Getter;
    use Slate\Neat\Attribute\Property;
    use Slate\Neat\Attribute\SetOnce;
    use Slate\Neat\Attribute\Setter;

    class HttpRequest extends HttpPacket {
        #[SetOnce]
        protected int        $method;

        #[SetOnce]
        protected float      $version;
        
        #[SetOnce]
        protected $route = null;

        #[SetOnce]
        protected ?Collection $parameters = null;

        protected ?StreamReader $bodyStream = null;

        #[SetOnce]
        protected Collection $query;

        #[SetOnce]
        protected Uri $uri;

        protected ?string $inputSource = null;

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

        #[Getter("source")]
        public function getSource(): string {
            $contentType = $this->headers["content-type"] ?? "";

            if(!$this->inputSource) {
                $this->inputSource = 
                    (\Str::contains($contentType, "/json") || \Str::contains($contentType, "+json")
                        ? "json"
                        : ($this->method & (HttpMethod::GET | HttpMethod::HEAD)
                            ? "query"
                            : "form"
                        )
                    );
            }

            return $this->inputSource;
        }

        #[Getter("input")]
        public function getInput(): Collection {
            return match($this->source) {
                "json" => collect($this->getBody()->json(assert: true)),
                "query" => collect($this->uri->query),
                "form" => $this->query,
                default => null
            };
        }

        #[Getter("parameters")]
        public function getParameters(): Collection {
            return $this->parameters ?? new Collection();
        }
        
        #[Setter("parameters")]
        public function setParameters(array $parameters): void {
            if($this->parameters !== null)
                throw new \Error("Unable to set the request parameters as it has already been set.");

            $this->parameters = new Collection($parameters, Collection::READABLE);
        }

        #[Setter("route")]
        public function setRoute(Route $route): void {
            if($this->route !== null)
                throw new \Error("Unable to set the request route as it has already been set.");

            $this->route = $route;
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

        public function var(string $name): FieldPrepped {
            return (new FieldPrepped($name))->from($this->parameters, $this->uri->query, $this->query);
        }

        
        public function bool(string $name, string $errorMessage = null): bool {
            return $this->var($name)->bool($errorMessage);
        }
        
        public function int(string $name, string $errorMessage = null): int {
            return $this->var($name)->int($errorMessage);
        }
        
        public function string(string $name, string $errorMessage = null): string {
            return $this->var($name)->string($errorMessage);
        }
        
        public function float(string $name, string $errorMessage = null): float {
            return $this->var($name)->float($errorMessage);
        }

        #[Getter("body")]
        public function getBody(): StreamReader {
            if(!$this->bodyStream)
                $this->bodyStream = new StreamReader(fopen("php://input", "r"));

            return $this->bodyStream;
        }

        public function __clone() {
            $this->uri = clone $this->uri;
            $this->parameters = clone $this->parameters;
        }

        public static function capture(): static {
            $uri = new Uri();
            $uri->scheme = \Str::lower(HttpEnvironment::getProtocol());
            $uri->host     = $_SERVER["HTTP_HOST"];

            $queries = ["get" => $_GET, "post" => $_POST];

            foreach($queries as $method => $query) {
                if(env("mvc.security.sanitise") === true)
                    $queries[$method] = Security::sanitise(
                        $query,
                        env("mvc.security.escapes") ?? ["\"", "'", "`"]
                    );
            }

            $uri->setPath(HttpEnvironment::getPath());
            $uri->query = $queries["get"];
            
            /** Load Version */
            $version = HttpEnvironment::getVersion();

            /** Load Method */
            $method = HttpMethod::getValue(\Str::uppercase(HttpEnvironment::getMethod()));

            /** Load Headers */
            $headers = \Arr::mapAssoc(
                HttpEnvironment::getHeaders(),
                fn(string $key, string $value) => [\Str::lower($key), $value]
            );

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