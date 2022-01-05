<?php

/** 
 * A facade to contain all class related functions.
 */
final class Cls extends \Slate\Utility\Facade {
    /**
     * Check whether a given class or object is the subclass or
     * the instance of a/multiple parent classes.
     * 
     * @param string|object $class
     */
    public static function isSubclassInstanceOf(string|object $targetClassObject, string|array $parentClassObject): bool {
        if(is_string($parentClassObject))
            $parentClassObject = [$parentClassObject];

        return \Arr::any($parentClassObject, function($parentClassObject) use($targetClassObject) {
            return is_subclass_of($targetClassObject, $parentClassObject)
                || ($targetClassObject instanceof $parentClassObject);
        });
    }

    /**
     * Check whether a given class has a public method.
     *
     * @param string $class
     * @param string $method
     *
     * @return boolean
     */
    public static function hasPublicMethod(string $class, string $method): bool {
        return method_exists($class, $method) && is_callable([$class, $method]);
    }

    /**
     * Get all tthe subclasses of a given parent class.
     *
     * @param  string|object $parent
     * @return array
     */
    public static function getSubclassesOf(string|object $parent): array {
        return \Arr::filter(get_declared_classes(), function($class) use(&$parent) {
            return is_subclass_of($class, $parent);
        });
    }
    
    /**
     * Get the parent classes of a given class.
     *
     * @param  string|object $class
     * @return array
     */
    public static function getParents(string|object $class): array {
        return \Arr::values(class_parents($class));
    }
    
    /**
     * Get the Interfaces of a given class.
     *
     * @param  mixed $class
     * @return void
     */
    public static function getInterfaces(mixed $class): array {
        if(($implements = class_implements($class)) === false)
            return [];

        return \Arr::values($implements);
    }
    
    /**
     * Dynamically instantiate a class with given arguments.
     *
     * @param  mixed $class
     * @param  array $arguments
     * @return object
     */
    public static function instantiate(string $class, array $arguments = []): object {
        $reflection = new ReflectionClass($class);

        return $reflection->newInstanceArgs($arguments);
    }
 
    /**
     * Check if a class has a constant by the given name.
     *
     * @param  string|object $class
     * @param  string        $constant
     * @return bool
     */
    public static function hasConstant(string|object $class, string $constant): bool {
        if(is_object($class))
            $class = $class::class;

        return defined($class."::".$constant);
    }
    
    /**
     * Check if a class has a given set of interfaces.
     *
     * @param  string|object $class
     * @param  string|array  $interfaces
     * @return bool
     */
    public static function hasInterfaces($class, array|string $interfaces): bool {
        if(is_string($interfaces))
            $interfaces = [$interfaces];

        $classInterfaces =  \Cls::getInterfaces($class);

        return \Arr::all($interfaces, function($interface) use($classInterfaces) {
            return \Arr::contains($classInterfaces, $interface);
        });
    }
    
    /**
     * Check if a given class has a given interface.
     *
     * @param  string|object $class
     * @param  string        $interface
     * @return bool
     */
    public static function hasInterface(string|object $class, string $interface): bool {
        return \Arr::contains(\Cls::getInterfaces($class), $interface);
    }
    
    /**
     * @see \Cls::hasInterfaces
     */
    public static function implements(string|object $class, string|array $interface): bool {
        return \Cls::hasInterface($class, $interface);
    }
    
    /**
     * Check if an object is the instance of one or more classes.
     *
     * @param  object       $object
     * @param  string|array $parent
     * @return bool
     */
    public static function isInstanceOf(object $targetClassObject, string|array $parentClassObject): bool {
        if(is_string($parentClassObject))
            $parentClassObject = [$parentClassObject];

        return \Arr::any($parentClassObject, function($parentClassObject) use($targetClassObject) {
            return $targetClassObject instanceof $parentClassObject;
        });
    }
    
    /**
     * Check if an object or class is the subclass of one or more classes.
     *
     * @param  string|object $class
     * @param  string|array $parent
     * @return bool
     */
    public static function isSubclassOf(string|object $class, string|array $parent): bool {
        if(is_array($parent)) {
            return \Arr::any($parent, function($parent) use($class) {
                return is_subclass_of($class, $parent);
            });
        }

        return is_subclass_of($class, $parent);
    }
    
    /**
     * Check if a class or object has a method.
     *
     * @param  string|object $any
     * @param  string        $method
     * @return bool
     */
    public static function hasMethod(string|object $any, string $method): bool{
        return method_exists($any, $method);
    }
    
    /**
     * Bind a class method with given arguments, providing same functionality as the javascript
     * equivalent.
     *
     * @param  string|object $class
     * @param  string        $method
     * @param  array         $arguments
     * @return mixed
     */
    public static function bindMethod(string|object $class, string $method, array $arguments = []): mixed {
        return (fn() => \Cls::callMethod($class, $method, $arguments));
    }
    
    /**
     * Call the method of a given class or object.
     *
     * @param  string|object $class
     * @param  string        $method
     * @param  array         $arguments
     * @return mixed
     */
    public static function callMethod(string|object $class, string $method, array $arguments = []): mixed {
        return \Fnc::call((!is_string($class) ? [$class, $method] : $class . "::" . $method), $arguments);
    }
    
    /**
     * Get the name of a class.
     *
     * @param  string|object $any
     * @return string
     */
    public static function getName(string|object $any): string {
        return \Str::afterLast(is_object($any) ? get_class($any) : $any, "\\");
    }

    /**
     * Get the value of a constant within a class.
     *
     * @param  string|object $class
     * @param  string        $name
     * @param  mixed         $fallback
     * @return mixed
     */
    public static function getConstant(string|object $class, string $name, mixed $fallback = null): mixed {
        if(is_object($class)) $class = get_class($class);

        $reference = $class."::".$name;

        if(defined($reference)) {
            $constant = constant($reference);
            
            return $constant;
        }
        
        return $fallback;
    }

    /**
     * Get the traits of a given class or object.
     *
     * @param  string|object $class
     * @param  bool          $deep
     * @param  bool          $autoload
     * @return array
     */
    public static function getTraits(string|object $class, bool $deep = true, bool $autoload = false): array {
        $traits = [];
            
        if($deep) {
            // Get traits of all parent classes
            do {
                $traits = \Arr::merge(
                    \Cls::getTraits($class, false, $autoload),
                    $traits
                );
            } while ($class = get_parent_class($class));
        
            // Get traits of all parent traits
            $traitsToSearch = $traits;

            while (!empty($traitsToSearch)) {
                $newTraits = \Cls::getTraits(\Arr::pop($traitsToSearch), false, $autoload);
                $traits = \Arr::merge($newTraits, $traits);
                $traitsToSearch = \Arr::merge($newTraits, $traitsToSearch);
            };
        
            foreach ($traits as $trait => $same) {
                $traits = \Arr::merge(class_uses($trait, $autoload), $traits);
            }
        }
        else {
            $traits = class_uses($class, $autoload);
        }
        
        return \Arr::unique($traits);
    }
    
    /**
     * Get the constants of a given class.
     *
     * @param  string $class
     * @return array
     */
    public static function getConstants(string $class): array {
        return (new \ReflectionClass($class))->getConstants();
    }

    /**
     * Check if a class or object has a given trait.
     * 
     * @param  string|object $class
     * @param  string        $trait
     * @param  bool          $deep
     * @param  bool          $autoload
     * 
     * @return bool
     */
    public static function hasTrait(string $class, string $trait, bool $deep = false, bool $autoload = false): bool {
        return \Arr::contains(\Cls::getTraits($class, $deep, $autoload), $trait);
    }

    /**
     * Check if a given class or object has a parent anywhere in its inheritance tree.
     * 
     * @param string|object $class
     * @param string        $parent
     * @param bool          $immediate
     * 
     * @return bool
     */
    public static function hasParent(string $class, string $parent, bool $immediate = false): bool {
        return get_parent_classs($class)[$parent] !== NULL;
    }
}