<?php declare(strict_types = 1);

namespace Slate\Media {

    use Error;
    use Slate\Exception\ParseException;

    /**
     * A class for handling and extending URIs and Filepaths.
     * Note; when handling dotlinks and noslash paths, it will not resolve them
     * to real paths and only provides combinational path logic.
     * 
     * TODO: add asserthas(parts)
     */
    class Uri {
        const SCHEME   = (1<<0);
        const HOST     = (1<<1);
        const PORT     = (1<<2);
        const USER     = (1<<3);
        const PASS     = (1<<4);
        const CREDS    = Uri::USER | Uri::PASS;
        const PATH     = (1<<5);
        const QUERY    = (1<<6);
        const FRAGMENT = (1<<7);
        const ALL      =
            Uri::SCHEME ^
            Uri::HOST   ^
            Uri::PORT   ^
            Uri::USER   ^
            Uri::PASS   ^
            Uri::PATH   ^
            Uri::QUERY  ^
            Uri::FRAGMENT
        ;

        public const RELATIVE         = (1<<0);
        public const RELATIVE_DOTLINK = (1<<1);

        public ?string $scheme   = null;
        public ?string $host     = null;
        public ?int    $port     = null;
        public ?string $user     = null;
        public ?string $pass     = null;
        public array   $query    = [];
        public ?string $fragment = null;
        public ?UriPath $path     = null;

        protected int  $relative = 0;

        public function __construct(string $uri = null) {
            if($uri !== null) {
                $parsed = static::parse($uri);

                $this->scheme   = $parsed["scheme"];
                $this->host     = $parsed["host"];
                $this->port     = $parsed["port"];
                $this->user     = $parsed["user"];
                $this->pass     = $parsed["pass"];

                if(!empty($parsed["path"]))
                    $this->setPath($parsed["path"]);

                $query = [];

                if(!empty($parsed["query"]))
                    parse_str($parsed["query"], $query);
                
                $this->query    = is_array($query) ? $query : [];
                $this->fragment = $parsed["fragment"];
            }
        }

        public function __clone() {
            if($this->path !== null)
                $this->path = clone $this->path;
        }

        public function __invoke(): string {
            return $this->toString();
        }
        
        /**
         * Apply a parent uri to the current uri.
         *
         * @param string|Uri $uri
         *
         * @return static
         */
        public function apply(string|Uri $parentUri): void {
            $parentUri = is_string($parentUri) ? (new static($parentUri)) : (clone $parentUri); 

            if($differentHost = (($this->host && $parentUri->host) ? ($this->host !== $parentUri->host) : (!$this->host && $parentUri->host))) {
                $this->scheme = $parentUri->scheme;
                $this->host   = $parentUri->host;
                $this->user   = $parentUri->user;
                $this->pass   = $parentUri->pass;
                $this->port   = $parentUri->port;
            }

            if($this->path !== null && !$differentHost && $parentUri->path !== null) {
                $this->path->apply($parentUri->path);
            }
            else {
                $this->path = $parentUri->path;
            }

            if(!$differentHost)
                $this->query = \Arr::merge($this->query, $parentUri->query);


        }
        
        public function getScheme(): ?string {
            return $this->scheme;
        }

        public function getHost(): ?string {
            return $this->host;
        }

        public function getPort(): ?string {
            return $this->port;
        }

        public function getUser(): ?string {
            return $this->user;
        }

        public function getPass(): ?string {
            return $this->pass;
        }

        public function getPath(): ?UriPath {
            return $this->path;
        }

        public function getQuery(): ?string {
            return http_build_query($this->query);
        }

        public function getFragment(): ?string {
            return $this->fragment;
        }

        public function setPath(string $path): void {
            $this->path = new UriPath($path);
        }

        public static function parse(string $uri, int $components = -1) {
            $parsed = parse_url($uri);
        
            if($parsed === null)
                throw new ParseException([$uri], ParseException::ERROR_URI_PARSE);
        
            if($parsed["path"] !== null) {
                $parsed["pathIncludingQuery"] = $parsed["path"];
            
                if(\Arr::hasKey($parsed, "query"))
                    $parsed["pathIncludingQuery"] = $parsed["path"].\Str::afterLast($uri, $parsed["path"]);
            }
        
            return $parsed;
        }

        public function toString(int $flags = Uri::ALL, string $delimiter = "/", bool $html = true): ?string {
            $url = "";
            
            if($this->host && \Integer::hasBits($flags, static::HOST)) {
                $url .= ($this->scheme  && \Integer::hasBits($flags, static::SCHEME) ? $this->scheme."://" : ($html ? "//" : ""));

                if($this->user  && \Integer::hasBits($flags, static::CREDS)) {
                    $url .= $this->user;

                    if($this->pass)
                        $url .= ":".$this->pass;

                    $url .= "@";
                }

                $url .= $this->host;

                if($this->port !== null && \Integer::hasBits($flags, static::PORT)) {
                    $url .= ":".$this->parts;
                }
            }

            if($this->getPath() && \Integer::hasBits($flags, static::PATH)) {
                $url .= $this->path->toString($delimiter);
            }
            
            if(!\Arr::isEmpty($this->query) && \Integer::hasBits($flags, static::QUERY)) {
                $url .= "?".\Str::trimPrefix($this->getQuery(), "?");
            }

            if($this->fragment !== null && \Integer::hasBits($flags, static::FRAGMENT)) {
                $url .= "#".$this->fragment;
            }

            return !\Str::isEmpty($url) ? $url : null;
        }

        public function forWeb(): bool {
            return $this->host !== null;
        }

        public function forFileSystem(): bool {
            return
                \Arr::none([
                    $this->scheme,
                    $this->port,
                    $this->user,
                    $this->pass,
                    $this->fragment
                ]) && \Arr::isEmpty($this->query);
        }

        public function format(string $format): string {
            return \Str::format($format, [
                "scheme"    => $this->scheme,
                "host"      => $this->host,
                "port"      => $this->port,
                "user"      => $this->user,
                "pass"      => $this->pass,
                "path"      => $this->getPath()->toString(),
                "query"     => $this->getQuery(),
                "fragment"  => $this->fragment,

                "extension" => $this->extension,
                "directory" => $this->directory,
                "filename"  => $this->filename
            ]);
        }
    }
}

?>