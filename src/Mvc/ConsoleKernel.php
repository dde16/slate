<?php

namespace Slate\Mvc {

    use Slate\Data\Repository\IRepository;
    use Slate\Exception\HttpException;
    use Slate\Exception\IOException;
    use Slate\Exception\PathNotFoundException;
    use Slate\Exception\SlateException;
    use Slate\Foundation\Kernel;
    use Slate\Foundation\Stager;
    use Slate\IO\SysvSharedMemoryQueue;
    use Slate\IO\SysvSharedMemoryTableQueue;
    use Slate\Sql\SqlConnection;
    use Slate\Sql\SqlConnectionFactory;
    use Throwable;

    class ConsoleKernel extends Kernel {
        public const NONE          = Kernel::NONE;
        public const CONFIGURE     = (1<<0);
        public const VERIFY        = (1<<1);
        public const HANDLER       = (1<<2);
        public const CONNECTIONS   = (1<<3);
        public const QUEUES        = (1<<4);
        public const REPOSITORIES  = (1<<5);

        protected array  $connections;
        protected ?string $primaryConnection;
        
        protected array  $queues;
        protected ?string $primaryQueue;
        
        protected array  $repositories;
        protected ?string $primaryRepository;

        protected string $root;

        protected ?Throwable $lastError;

        public const STAGES = [
            self::CONFIGURE,
            self::VERIFY,
            self::HANDLER,
            self::CONNECTIONS,
            self::REPOSITORIES,
            self::QUEUES
        ];

        public function __construct(string $root) {
            $this->root              = \Path::normalise($root);

            $this->connections       = [];
            $this->primaryConnection = null;

            $this->queues            = [];
            $this->primaryQueue      = null;

            $this->repositories      = [];
            $this->primaryRepository = null;

            $this->lastError = null;
        }

        #[Stager(self::CONFIGURE)]
        protected function configure(): void {
            $root     = $this->root;
            $resolved = \Path::resolve($root);

            if($resolved !== null) {
                $root = $resolved;

                if(!is_dir($root))
                    throw new IOException("Root directory '$root' was not found.", IOException::ERROR_DIR_NOT_FOUND);
                
                Env::set("mvc.root.path", \Path::normalise($root));
            }
            else {
                throw new IOException("Unable to resolve the root path '$root'.", IOException::ERROR_UNRESOLVABLE_PATH);
            }
        }

        /**
         * Verify the configuration.
         * 
         * @return void
         */
        #[Stager(self::VERIFY)]
        protected function verify(): void {
            $required = [
                "mvc.root.path",
                "mvc.index.path",
                "mvc.public.path",
                "mvc.view.path",
            ];

            /** Check if the required config variables are set. */
            if(Env::has($required)) {
                /** Normalise the index path incase of irregular leading or trailing slashes */
                Env::modify("mvc.index.path", function($value) {
                    return \Path::normalise($value);
                });

                /** Resolve any paths bound to its root */
                foreach(Env::$boundpaths as $rootkey => $children) {
                    /** Get the root path */
                    $root = Env::get($rootkey, [ "important" => true ]);

                    foreach(array_merge($children) as $index => $key) {
                        $value = Env::get($key);
                        $key   = \Arr::join(\Arr::slice(\Str::split($key, "."), 0, -1), ".").".relative.path";
    
                        Env::set($key, $value);
                    }
    
                    /** Modify all of these values to make absolute paths */
                    Env::modify($children, function($value, $key) use($root) {
                        return $root.$value;
                    });
                }
            }
            else {
                throw new \UnexpectedValueException("The configuration file does not have the required variables " . \Arr::join(\Arr::filter(
                    $required,
                    function($required) {
                        return !Env::has($required);
                    }
                ), ", "));
            }
        }

        /**
         * Register the application repositories from the configuration file.
         * 
         * @return void
         */
        #[Stager(self::CONNECTIONS)]
        protected function connections(): void {
            if(Env::has("connections")) {
                if(is_array($connections = Env::get("connections"))) {
                    foreach($connections as $name => $connection) {
                        $driver = $connection["driver"];

                        if(!is_string($driver))
                            throw new \Error("Connection '{}' must have a driver.");

                        if($connection["default"]) {
                            if($this->primaryConnection !== null)
                                throw new \Error(\Str::format(
                                    "Trying to set '{}' as primary connection where '{}' is already the primary connection.",
                                    $name,
                                    $this->primaryConnection
                                ));

                            $this->primaryConnection = $name;
                        }

                        $this->connections[$name] = SqlConnectionFactory::create(
                            $driver, \Arr::except($connection, ["driver", "default"]));
                    }
                }
                else {
                    throw new \Error("Configuration variable 'connections' must be of type array.");
                }
            }
        }

        public function conn(?string $name = null): SqlConnection|null {
            return @$this->connections[$name ?: $this->primaryConnection];
        }

        /**
         * Register the application queues from the configuration file.
         * 
         * @return void
         */
        #[Stager(self::QUEUES)]
        protected function queues(): void {
            if(Env::has("queues")) {
                if(is_array($queues = Env::get("queues"))) {
                    foreach($queues as $name => $queue) {
                        $type = $queue["type"];

                        if(!is_string($type))
                            throw new \Error("Queue '{}' must have a type.");
                    
                        if($queue["default"]) {
                            if($this->primaryQueue !== null)
                                throw new \Error(\Str::format(
                                    "Trying to set '{}' as primary queue where '{}' is already the primary queue.",
                                    $name,
                                    $this->primaryQueue
                                ));

                            $this->primaryQueue = $name;
                        }

                        $this->queues[$name] = QueueFactory::create($type, \Arr::except($queue, ["type", "default"]));
                    }
                }
                else {
                    throw new \Error("Configuration variable 'queues' must be of type array.");
                }
            }
        }

        public function queue(string $name = null): SysvSharedMemoryTableQueue|SysvSharedMemoryQueue|null {
            return @$this->queues[$name ?: $this->primaryQueue];
        }

        /**
         * Register the application repositories from the configuration file.
         * 
         * @return void
         */
        #[Stager(self::REPOSITORIES)]
        protected function repositories(): void {
            if(Env::has("repositories")) {
                if(is_array($repositories = Env::get("repositories"))) {
                    foreach($repositories as $name => $repo) {
                        $type = @$repo["type"];

                        if(!is_string($type))
                            throw new \Error("Repository '{}' must have a type.");

                        if(@$repo["default"]) {
                            if($this->primaryConnection !== null)
                                throw new \Error(\Str::format(
                                    "Trying to set '{}' as primary repository where '{}' is already the primary repository.",
                                    $name,
                                    $this->primaryConnection
                                ));

                            $this->primaryRepository = $name;
                        }

                        $this->repositories[$name] = RepoFactory::create($type, \Arr::except($repo, ["type", "default"]));
                    }
                }
                else {
                    throw new \Error("Configuration variable 'repositories' must be of type array.");
                }
            }
        }

        public function repo(string $name = null): IRepository {
            $name = $name ?: $this->primaryRepository;

            if(!\Arr::hasKey($this->repositories, $name)){
                throw new \Error("Unknown repository {$name}.");
            }

            return $this->repositories[$name];
        }

        /**
         * Register a global exception handler. This should not be used as the
         * main exception handler but as a backup in case an error is raised
         * while handling another.
         * 
         * @return void
         */
        #[Stager(self::HANDLER)]
        protected function handler(): void {
            $dnt = false;

            set_exception_handler(function($throwable) {
                $this->lastError = $throwable;
                try {
                    /** Whether the error page has been resolved or not */
                    $errorPageResolve = false;
                    
                    /** Set response code to one of HttpException or 500 internal server error */
                    $httpCode =
                        \Cls::isSubclassInstanceOf($throwable, HttpException::class)
                            ? ($throwable->httpCode)
                            : 500;

                    $this->response->status         = $httpCode;

                    Env::use([
                        "mvc.root.path",
                        "mvc.view.path",
                        "mvc.error.page.path" ],
                        function($mvcRootDirectory, $mvcViewsDirectory, $mvcErrorPage) use($throwable, &$errorPageResolve) {
                            /** Check if all of the paths are not empty */
                            if($mvcViewsDirectory !== null && $mvcViewsDirectory !== null && $mvcErrorPage !== null) {
                                /** Normalise the error page path */
                                $mvcErrorPage = \Path::normalise($mvcErrorPage);

                                /** Check if the views directory exists */
                                if(\Path::exists($mvcViewsDirectory)) {
                                    /** Error page must be in the views directory */
                                    $mvcErrorPage = $mvcViewsDirectory.$mvcErrorPage;

                                    /** Check if the page exists and is safe */
                                    if($mvcErrorPage = \Path::safe($mvcRootDirectory, $mvcErrorPage)) {
                                        /** Set the data of the exception */
                                        $_DATA = [
                                            "custom" => [
                                                "Throwable" => $throwable
                                            ]
                                        ];

                                        /** Get page output */
                                        ob_start();
                                        include($mvcErrorPage);
                                        $body = ob_get_contents();
                                        ob_end_clean();

                                        $errorPageResolve = true;

                                        $this->response->body = $body;
                                    }
                                }
                            }
                        }
                    );

                    /** If there is no error page */
                    if(!$errorPageResolve) {
                        $verbosePageAllowed = false;

                        /** Check if the verbose flag is set */
                        if(($mvcVerbose = Env::get("mvc.verbose"))  !== null) {
                            $verbosePageAllowed = \Boolean::tryparse($mvcVerbose);
                        }

                        /** If verbose then display output */
                        if($verbosePageAllowed) {
                            $this->response->headers["Content-Type"] = "text/html";
                            echo (SlateException::getHtml($throwable, $httpCode));
                        }
                        else {
                            $dnt = true;
                            throw $throwable;
                        }
                    }
                }
                catch(\Throwable $throwable) {
                    if($dnt === false) {
                        echo "An error occured, while handling another error; verbosity will be ignored.";
                        echo SlateException::getHtml($throwable);
                    }
                    else {
                        throw $throwable;
                    }
                }
                finally {
                    $this->response->send();
                }
            });
        }
    }
}

?>