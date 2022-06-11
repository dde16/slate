<?php declare(strict_types = 1);

namespace Slate\Foundation\Console\Command {

    use ReflectionClass;
    use ReflectionNamedType;
    use ReflectionUnionType;
    use Slate\Foundation\Console\Command;

    class ClassDocsCommand extends Command {
        public const NAME = "class:phpdocs";
        public const ARGUMENTS = [
            "class" => [
                "aliases" => ["-c", "--class"]
            ],
            "pattern" => [
                "aliases" => ["-i", "--pattern"]
            ],
            "filters" => [
                "aliases" => ["-f", "--filter"]
            ],
            "parents" => [
                "aliases" => ["-p", "--parents"],
                "nargs" => 0
            ]
        ];

        public function handle(string $class, bool $parents = false): void {
            $reflection = new ReflectionClass($class);

            debug("/**");
            
            foreach($reflection->getMethods() as $method) {
                $doc = ["@method"];
                $parameters = [];

                if((!$parents ? $method->getDeclaringClass()->getName() === $class : true) && !\Arr::contains(["__construct", "__destruct", "__clone", "__wakeup", "__get", "__set"], $method->getName())) {

                    if($method->isStatic()) {
                        $doc[] = "static";
                    }

                    if($method->hasReturnType()) {
                        $returnType = $method->getReturnType();

                        if($returnType instanceof ReflectionUnionType) {
                            $doc[] = "mixed";
                        }
                        else {
                            $doctype = $returnType->getName();

                            $doc[] = ($returnType->allowsNull() && $doctype !== "mixed" ? '?' : '') . $doctype;
                        }
                    }
                    else {
                        $doc[] = "void";
                    }

                    foreach($method->getParameters() as $parameter) {
                        $doctype = "mixed";

                        if($parameter->hasType()) {
                            $type = $parameter->getType();

                            if($type instanceof ReflectionUnionType) {
                                $doctype = \Arr::join(\Arr::map($type->getTypes(), fn(ReflectionNamedType $unionType): string => $unionType->getName()), "|");
                            }
                            else {
                                $doctype = $type->getName();
                            }
                        }

                        $parameters[$parameter->getName()] = $doctype;
                    }

                    $doc[] = $method->getName().\Arr::list(\Arr::values(\Arr::mapAssoc($parameters, fn(string $name, ?string $type): array => [$name, ($type ? "$type " : "")."\${$name}"])), ", ", "", "()");

                    debug(" * " . \Arr::join($doc, " "));
                }
            }

            debug(" */");
        }
    }
}

?>