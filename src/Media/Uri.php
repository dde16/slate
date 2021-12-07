<?php

namespace Slate\Media {
    //TODO: replace file path properties with SplFileInfo
    /**
     * A class for handling and extending URIs and Filepaths.
     * Note; when handling dotlinks and noslash paths, it will not resolve them
     * to real paths and only provides combinational path logic.
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
                $parsed = parse_url($uri);

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
        
        public function apply(string|Uri $uri): static {
            $uri = is_string($uri) ? (new static($uri)) : (clone $uri); 
            
            if(!$this->host && $uri->host) {
                $this->scheme = $uri->scheme;
                $this->host   = $uri->host;
                $this->user   = $uri->user;
                $this->pass   = $uri->pass;
                $this->port   = $uri->port;
            }

            if($this->path !== null) {
                $this->path->apply($uri->path);
            }
            else {
                $this->path = $uri->path;
            }

            $this->query = \Arr::merge($this->query, $uri->query);

            return $uri;
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
                $path = $this->path->toString($delimiter);
                $url .= !\Str::isEmpty($url) ? \Path::normalise($path) : $path;
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