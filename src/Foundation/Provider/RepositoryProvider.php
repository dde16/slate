<?php declare(strict_types = 1);

namespace Slate\Foundation\Provider {

    use Slate\Data\Repository\IRepository;
    use Slate\Facade\App;
    use Slate\Foundation\Provider;
    use Slate\Mvc\Env;
    use Slate\Mvc\RepoFactory;

    class RepositoryProvider extends Provider  {
        public function register(): void {
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

            App::macro("repos", function(): array {
                return array_keys($this->repositories);
            });

            App::macro("repo", function(string $name = null): IRepository {
                $name = $name ?: $this->primaryRepository;
    
                if(!\Arr::hasKey($this->repositories, $name)){
                    throw new \Error("Unknown repository {$name}.");
                }
    
                return $this->repositories[$name];
            });
        }
    }
}

?>