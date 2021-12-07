<?php

namespace Slate\Foundation\Provider {

    use Slate\Facade\App;
    use Slate\Foundation\Provider;
    use Slate\Mvc\Env;
    use Slate\Sql\SqlConnection;
    use Slate\Sql\SqlConnectionFactory;

    class ConnectionProvider extends Provider {
        public function register(): void {
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

            App::contingentMacro("conn", function(?string $name = null): SqlConnection|null {
                return @$this->connections[$name ?: $this->primaryConnection];
            });
        }
    }
}

?>