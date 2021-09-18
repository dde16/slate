<?php

namespace Slate\Media {
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

        const RELATIVE_DOT     = 1;
        const RELATIVE_NOSLASH = 2;

        public ?string $scheme   = null;
        public ?string $host     = null;
        public ?int    $port     = null;
        public ?string $user     = null;
        public ?string $pass     = null;
        public array   $query    = [];
        public ?string $fragment = null;
        public ?string $path     = null;

        public ?string $extension = null;
        public ?string $directory = null;
        public ?string $filename  = null;

        

        protected int     $relative = 0;

        public function __construct(string $uri = null) {
            if($uri !== null) {

                $parsed = parse_url($uri);

                // http_build_query()

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
                
                $this->query    = $query;
                $this->fragment = $parsed["fragment"];
            }
        }

        public function __invoke(): string {
            return $this->toString();
        }

        public function extend(string $url): static {
            $url = new static($url);
            
            if(!$url->host) {
                $url->scheme = $this->scheme;
                $url->host   = $this->host;
                $url->user   = $this->user;
                $url->pass   = $this->pass;
                $url->port   = $this->port;
            }
            
            if($url->relative) {
                $split = \Str::split(\Str::trimAffix($this->getPath(), "/"), "/");

                $url->setPath(
                    "/".\Arr::join(count($split) === 1 ? $split : \Arr::slice($split, 0, -1), "/") . \Path::normalise($url->getPath())
                );
            }

            $url->relative = 0;

            return $url;
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

        public function getPath(): ?string {
            $parts = [$this->directory !== null ? \Str::trimAffix($this->directory, "/") : null, $this->getBasename()];

            $path = \Arr::join(\Arr::filter($parts), "/");

            return "/$path";
        }

        public function getQuery(): ?string {
            return http_build_query($this->query);
        }

        public function getFragment(): ?string {
            return $this->fragment;
        }

        public function getExtension(): ?string {
            return $this->extension;
        }

        public function getDirectory(): ?string {
            return $this->directory;
        }

        public function getFilename(): ?string {
            return $this->filename;
        }

        public function getBasename(): string {
            return $this->filename.($this->extension ? ("." . $this->extension) : "");
        }

        public function setPath(string $path): void {
            $dotRelative     =  \Str::startswith($path, ".");
            $noSlashRelative = !\Str::startswith($path, "/");

            if($dotRelative)
                $this->relative = self::RELATIVE_DOT;
            
            if($noSlashRelative)
                $this->relative = self::RELATIVE_NOSLASH;

            if($this->relative !== 0)
                $path = \Str::trimPrefix($path, ".");

            $pathinfo = pathinfo($path);

            $directory = $pathinfo["dirname"];

            if($directory !== null) {
                if($directory === "/" || $directory === "." || \Str::isEmpty($directory)) {
                    $directory = null;
                }
                else {
                    $directory = \Str::removeAffix($pathinfo["dirname"], "/");
                }
            }
            
            $this->extension = $pathinfo["extension"];
            $this->directory = $directory;
            $this->filename  = $pathinfo["filename"] !== null ? \Str::removeAffix($pathinfo["filename"], "/") : $pathinfo["filename"];
        }

        public function toString(int $flags = Uri::ALL, bool $html = true): ?string {
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
                if($this->relative) {
                    if($html) {
                        $url .= ".".\Str::addPrefix($this->getPath(), "/");
                    }
                    else {
                        $url .= \Str::removePrefix($this->getPath(), "/");
                    }
                }
                else {
                    $url .= $this->getPath();
                }
            }
            
            if(!\Arr::isEmpty($this->query) && \Integer::hasBits($flags, static::QUERY)) {
                $url .= "?".\Str::trimPrefix($this->getQuery(), "?");
            }

            if($this->fragment !== null && \Integer::hasBits($flags, static::FRAGMENT)) {
                $url .= "#".$this->fragment;
            }

            return !\Str::isEmpty($url) ? $url : null;
        }

        public function format(string $format): string {
            return \Str::format($format, [
                "scheme"    => $this->scheme,
                "host"      => $this->host,
                "port"      => $this->port,
                "user"      => $this->user,
                "pass"      => $this->pass,
                "path"      => $this->getPath(),
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