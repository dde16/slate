<?php

namespace Slate\Foundation {

    use ReflectionUnionType;
    use Slate\Foundation\Console\Command;
    use Slate\Foundation\Console\CommandOption;
    use Slate\Metalang\Attribute\AttributeCall;

    abstract class ConsoleKernel extends Kernel {
        protected array $argv;
    
        public function __construct(string $root, array $argv) {
            $this->root = $root;
            $this->argv = $argv;
        }

        public static function coloured(string $text, int $colour): string {
            return "\033[{$colour}m{$text}\033[0m";
        }
    
        #[AttributeCall(Stager::class)]
        public function stagerInstanceImplementor(string $name, array $arguments, object $next): mixed {
            if(($stager = static::design()->getAttrInstance(Stager::class, $name)) !== null) {
                if($this->past($stager->getFlag()))
                    throw new \Error(\Str::format(
                        "Stager {}::{}() has already been called.",
                        static::class, $stager->parent->getName()
                    ));
    
                $return = [true, $this->{$stager->parent->getName()}(...$arguments)];
    
                $this->stage |= $stager->getFlag();
    
                return $return;
            }
    
            return ($next)($name, $arguments);
        }
    
        public function getHelp(string $basename): string {
            $help = <<<STR
    Usage: {} [OPTIONS] COMMAND [ARGS]...
    
    Options:
    {}
    
    Commands:
    {}
    STR;
    
            return \Str::format($help, $basename, \Arr::join(\Arr::map(
                static::design()->getAttrInstances(Command::class),
                function($command) {
                    return $command->getName();
                }
            ), "\n"));
        }
    
        public function getCommand(string $name): Command|null {
            return \Arr::first(
                static::design()->getAttrInstances(Command::class),
                function($command) use($name) {
                    return $command->getName() === $name;
                }
            );
        }
    
        public function getCommandOption(string $name): CommandOption|null {
            return \Arr::first(
                static::design()->getAttrInstances(CommandOption::class),
                function($option) use($name) {
                    return \Arr::contains(
                        $option->getNames(),
                        $name
                    );
                }
            );
        }
    
        public function go(): void {
            parent::go();
    
            $basename  = $this->argv[0];
            $argv      = \Arr::slice($this->argv, 1);
            $nargs     = -1;
            $remaining = 0;
    
            if(!\Arr::isEmpty($argv)) {
                $options   = [];
                $command   = null;
                $arguments = [];
                $argument  = null;
    
                foreach($argv as $arg) {
                    if($command === null) {
                        if(($option = $this->getCommandOption($arg)) !== null) {
                            $this->{$option->parent->getName()} = true;
                        }
                        else if(($_command = $this->getCommand($arg)) !== null) {
                            $command = $_command;
                        }
                        else {
                            throw new \Error("Unknown command or option '{$arg}'.");
                        }
                    }
                    else {
                        if(($_argument = $command->getArgument($arg)) !== null) {
                            if($nargs !== -1 && $remaining > 0)
                                throw new \Error();
    
                            $argument = $_argument;
                            $remaining = $nargs = $argument->getNargs();
                            
                        }
                        else if($argument !== null) {
                            if($nargs === 0) {
                                $arguments[$argument->parent->getName()] = "true";
                            }
                            else if($nargs === -1 || $nargs !== 1 && $remaining !== 0) {
                                $arguments[$argument->parent->getName()][] = $arg;
                                $remaining--;
                            }
                            else if($nargs === 1 && $remaining === 1) {
                                $arguments[$argument->parent->getName()] = $arg;
                                $remaining--;
                            }
                            else {
                                throw new \Error("Unknown argument '$arg'");
                            }
                        }
                        else {
                            throw new \Error("Unknown argument '$arg'");
                        }
                    }
                }
    
                if($remaining > 0)
                    throw new \Error(\Str::format(
                        "Argument {} requires {} argument{}, {} passed.",
                        \Arr::join($argument->getNames(), "/"),
                        $nargs,
                        $nargs > 1 ? 's' : '',
                        $nargs - $remaining
                    ));
    
                foreach($command->getArguments() as $argument) {
                    $provided = \Arr::hasKey($arguments, $argument->parent->getName());
    
                    if($argument->isRequired() && !$provided) {
                        throw new \Error(\Str::format(
                            "Required argument {} was not provided.",
                            \Arr::join($argument->getNames())
                        ));
                    }
    
                    if($provided && $argument->parent->hasType()) {
                        $type = $argument->parent->getType();
                        $types = [];
    
                        if(!($type instanceof ReflectionUnionType)) {
                            $types = [$type];
                        }
    
                        $value = &$arguments[$argument->parent->getName()];
    
                        if(!is_array($value)) {
                        
                            $types = \Arr::map(
                                $types,
                                function($type) use($value) {
                                    $typeName = $type->getName();
    
                                    if(class_exists($typeName))
                                        throw new \Error();
    
                                    $typeClass = \Type::getByName($typeName);
    
                                    if($typeClass === \Obj::class)
                                        throw new \Error();
    
                                    if($typeClass === null) 
                                        throw new \Error("Unknown type.");
    
                                    return $typeClass::parse($value);
                                }
                            );
    
                            $value = \Arr::first($types);
    
                            if($value === null)
                                throw new \Error("Unable to cast value");
                        }
                    }
                }
    
                $command->parent->invokeArgs($this, $arguments);
    
                
            }
            else {
                echo $this->getHelp($basename);
            }
        }
    }
}

?>