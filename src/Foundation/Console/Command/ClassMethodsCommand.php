<?php declare(strict_types = 1);

namespace Slate\Foundation\Console\Command {
    use App\Lithium\LithiumController;
    use ReflectionClass;
    use ReflectionMethod;
    use Slate\Data\Table;
    use Slate\Foundation\Console\Command;

    class ClassMethodsCommand extends Command {
        public const NAME = "class:methods";
        public const ARGUMENTS = [
            "class" => [
                "aliases" => ["-c", "--class"]
            ],
            "pattern" => [
                "aliases" => ["-p", "--pattern"]
            ],
            "filters" => [
                "aliases" => ["-s", "--scope"]
            ],
            "columns" => [
                "aliases" => ["--columns"],
                "nargs" => -1
            ],
            "usagePaths" => [
                "aliases" => ["--usage"],
                "nargs" => -1
            ],
            "parents" => [
                "aliases" => ["--parents"],
                "nargs" => 0
            ],
            "show" => [
                "aliases" => ["--show"],
            ]
        ];

        /**
         * Handle
         *
         * @return void
         */
        public function handle(string $class, string $pattern = null, string $filters = null, array $columns = null, array $usagePaths = null, bool $parents = false, string $show = "unused"): void {
            $columns ??= [
                "name",
                "scope",
            ];

            $reflection = new ReflectionClass($class);
            $filter = null;

            if($filters !== null) {
                $filter = \Arr::or(\Arr::map(
                    \Str::split($filters, ","),
                    fn(string $filter): int => \Cls::getConstant(ReflectionMethod::class, "IS_".\Str::upper($filter))
                ));
            }

            $methods = $reflection->getMethods($filter);

            if($pattern !== null)
                $methods = \Arr::filter($methods, function(ReflectionMethod $method) use($pattern): bool {
                    return fnmatch($pattern, $method->getName());
                });

            if($parents === false)
                $methods = \Arr::filter($methods, function(ReflectionMethod $method) use($class): bool {
                    return $method->getDeclaringClass()->getName() === $class;
                });

            $counts = [];

            if($usagePaths !== null) {
                $names = \Arr::map(
                    \Arr::filter(
                        $methods,
                        fn(ReflectionMethod $method): bool => $method->isStatic()
                    ),
                    function(ReflectionMethod $method): string   {
                        return 
                            $method->getDeclaringClass()->getName()."::".$method->getName()
                        ;
                    }
                );

                foreach($usagePaths as $usagePath) {
                    \Path::assertDirExists($usagePath);

                    $columns[] = "$usagePath count";

                    foreach($names as $name) {
                        $counts[$name][$usagePath] = (\Arr::sum(\Arr::map(
                            array_slice(\Str::split(
                                \shell_exec("grep -Ric $name $usagePath"),
                                "\n"
                            ), 0, -1),
                            function(string $line): int {
                                return \Integer::tryparse(\Str::split($line, ":")[1]);
                            }
                        )));
                    }
                }
            }

            $table = new Table($columns);

            foreach($methods as $method) {
                $row = \Arr::associate($columns, null);

                $aggregateCount = 0;

                foreach($usagePaths ?? [] as $usagePath) {
                    $count = $counts[$method->getDeclaringClass()->getName()."::".$method->getName()][$usagePath] ?? 0;
                    $row["$usagePath count"] = $count;
                    $aggregateCount += $count;
                }


                if($usagePaths ? ($aggregateCount === 0 ? $show === "unused" : false) : $show !== "unused") {
                    if(\Arr::contains($columns, "name"))
                        $row["name"] = $method->getDeclaringClass()->getName()."::".$method->getName();
                    
                    if(\Arr::contains($columns, "file"))
                        $row["file"] = $method->getFileName();
                    
                    if(\Arr::contains($columns, "line"))
                        $row["line"] = $method->getStartLine();
                    
                    if(\Arr::contains($columns, "public"))
                        $row["public"] = $method->isPublic();
                    
                    if(\Arr::contains($columns, "protected"))
                        $row["protected"] = $method->isProtected();
                    
                    if(\Arr::contains($columns, "private"))
                        $row["private"] = $method->isPrivate();

                    if(\Arr::contains($columns, "scope")) {
                        if($method->isPublic())
                            $row["scope"] = "public";
        
                        if($method->isProtected())
                            $row["scope"] = "protected";
        
                        if($method->isPrivate())
                            $row["scope"] = "private";
                    }
                    
                    if(\Arr::contains($columns, "static"))
                        $row["static"] = $method->isStatic() ? "Y" : "";
                    
                    if(\Arr::contains($columns, "final"))
                        $row["final"] = $method->isFinal() ? "Y" : "";
                    
                    if(\Arr::contains($columns, "abstract"))
                        $row["abstract"] = $method->isAbstract() ? "Y" : "";

                    $row = \Arr::values(\Arr::only($row, $columns));

                    $table[] = $row;
                }
                
            }

            debug($table->toTableString());
        }
    }
}
