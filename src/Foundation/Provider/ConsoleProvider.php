<?php

namespace Slate\Foundation\Provider {

    use ReflectionUnionType;
    use Slate\Data\Iterator\ArrayExtendedIterator;
    use Slate\Foundation\Provider;

    class ConsoleProvider extends Provider {
        public function boot(): void {
            $basename  = $this->app->argv[0];
            $argv      = \Arr::slice($this->app->argv, 1);
            $nargs     = -1;
            $remaining = 0;
            $pkey      = 0;

    
            if(!\Arr::isEmpty($argv)) {
                $options   = [];
                $command   = null;
                $arguments = [];
                $argument  = null;

                $iter = new ArrayExtendedIterator($argv);

                while($iter->valid()) {
                    $arg = $iter->current();
                    
                    if($command === null) {
                        if(($option = $this->app->getCommandOption($arg)) !== null) {
                            $this->app->{$option->parent->getName()} = true;
                        }
                        else if(($_command = $this->app->getCommand($arg)) !== null) {
                            $command = $_command;
                        }
                        else {
                            throw new \Error("Unknown command or option '{$arg}'.");
                        }
                    }
                    else if($argument !== null) {
                        if($nargs === 0) {
                            $arguments[$argument->parent->getName()] = "true";
                        }
                        // If the argument requires many values (-1) or requires more than one value
                        else if(($nargs === -1 || $nargs !== 1)) {
                            $arguments[$argument->parent->getName()][] = $arg;

                            if($remaining-- === 0)
                                $argument = null;
                        }
                        else if($nargs === 1 && $remaining === 1) {
                            $arguments[$argument->parent->getName()] = $arg;
                            $remaining--;
                            $argument = null;
                        }
                        else {
                            debug($nargs);
                            debug($remaining);
                            throw new \Error("Unknown argument '$arg'");
                        }
                    }
                    else if(($_argument = $command->getArgument($arg)) !== null) {
                        if($nargs !== -1 && $remaining > 0)
                            throw new \Error();

                        $argument = $_argument;
                        $remaining = $nargs = $argument->getNargs();
                    }
                    else if(($_argument = $command->getArgument($pkey)) !== null) {
                        $argument = $_argument;
                        $remaining = $nargs = $argument->getNargs();

                        $iter->prev();

                        $pkey++;
                    }
                    else {
                        throw new \Error("Unknown argument '$arg'");
                    }

                    $iter->next();
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
                        echo $command->getHelp($basename);
                        return;
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
    
                $command->parent->invokeArgs($this->app, $arguments);
    
                
            }
            else {
                echo $this->app->getHelp($basename);
            }
        }
    }
}

?>