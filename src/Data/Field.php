<?php

namespace Slate\Data {

    use ArrayAccess;
    use Closure;
    use Slate\Exception\ParseException;

    /**
     * A class for matching fields with more utilities.
     */
    class Field {
        /**
         * The name of the field to get.
         *
         * @var string
         */
        protected string $name;

        /**
         * The fallback incase the array doesn't have the name as the key or the value fails validation.
         *
         * @var mixed
         */
        protected mixed $fallback;

        /**
         * Flag on whether there is a fallback set.
         *
         * @var boolean
         */
        protected bool $hasFallback;

        /**
         * Whether to remove the value from the source upon success.
         *
         * @var boolean
         */
        protected bool $temporary;

        /**
         * Custom validator for values, should return bool and on false with no fallback raise an error.
         *
         * @var Closure|null
         */
        protected ?Closure $validator;

        /**
         * Custom converter for values, should return the converted value and on fail raise a ParseException.
         *
         * @var Closure|null
         */
        protected ?Closure $converter;
    
        /**
         * Error message when value validation fails.
         *
         * @var string|null
         */
        protected ?string $validatorErrorMessage;
        
        /**
         * Error message to override that of the parse exception.
         *
         * @var string|null
         */
        protected ?string $converterErrorMessage;
    
        public function __construct(string $name) {
            $this->name = $name;
            $this->hasFallback = false;
            $this->temporary = false;
            $this->validator = null;
            $this->converter = null;
            $this->validatorErrorMessage      = null;
            $this->converterErrorMessage      = null;
        }
    
        public function fallback(mixed $fallback = null): static {
            $this->hasFallback = true;
            $this->fallback = $fallback;
    
            return $this;
        }
    
        public function validate(Closure|callable $validator, string $errorMessage = null): static {
            $this->validator = Closure::fromCallable($validator);
            $this->validatorErrorMessage = $errorMessage;
    
            return $this;
        }
    
        public function convert(Closure|callable $converter, string $errorMessage = null): static {
            $this->converter = Closure::fromCallable($converter);
            $this->converterErrorMessage = $errorMessage;
    
            return $this;
        }
    
        public function cast(string $type, string $errorMessage = null): static {
            if(!class_exists($type)) {
                if(($type = \Type::getByName($type)) === null) {
                    throw new \RuntimeException("Unknown type '$type'.");
                }
            }
    
            if(!\Cls::isSubclassOf($type, \ScalarType::class)) {
                throw new \RuntimeException("Type casts for fields must be scalar.");
            }
    
    
            return $this->convert(
                Closure::fromCallable([$type, "tryparse"]),
                \Str::format(
                    ($errorMessage !== null
                        ? $errorMessage
                        : "Unable to cast {name} to a {type}."
                    ),
                    ["type" => $type::NAMES[0]]
                )
            );
        }
    
        public function temporary(): static {
            $this->temporary = true;
    
            return $this;
        }

        public function array(string $errorMessage = null): mixed {
            return $this->validate(fn(mixed $value): bool => is_array($value), $errorMessage);
        }

        public function object(string $errorMessage = null): mixed {
            return $this->validate(fn(mixed $value): bool => is_object($value), $errorMessage);
        }
    
        public function bool(string $errorMessage = null): mixed {
            return $this->cast(\Boolean::class, $errorMessage);
        }
    
        public function int(string $errorMessage = null): mixed {
            return $this->cast(\Integer::class, $errorMessage);
        }
    
        public function string(string $errorMessage = null): mixed {
            return $this->cast(\Str::class, $errorMessage);
        }
    
        public function float(string $errorMessage = null): mixed {
            return $this->cast(\Real::class, $errorMessage);
        }
    
        protected function getFrom(ArrayAccess|array $source, bool|string $assert = true): array {
            $sourced = true;
    
            try {
                if(!\Arr::hasKey($source, $this->name)) {
                    if($assert !== false)
                        throw (new \RuntimeException(
                            \Str::format(
                                (is_string($assert)
                                    ? $assert
                                    : "Unable to get {name}."
                                ),
                                ["name" => $this->name]
                            )
                        ));

                    $sourced = false;
                }

    
                $value = $source[$this->name];
    
                if($this->validator && $sourced) {
                    if(!($this->validator)($value)) {
                        if($assert)
                            throw new \RuntimeException(
                                \Str::format(
                                    $this->validatorErrorMessage,
                                    ["name" => $this->name]
                                )
                                ??"Unable to validate {$this->name}."
                            );
    
                        $sourced = false;
                    }
                }
    
                if($this->converter && $sourced) {
                    try {
                        $value = ($this->converter)($value);
                    }
                    catch(ParseException $parseException) {
                        if($assert)
                            throw new \RuntimeException(
                                \Str::format(
                                    $this->converterErrorMessage,
                                    ["name" => $this->name]
                                )
                                ?? "Unable to parse {$this->name}."
                            );
                        
                        $sourced = false;
                    }
                }
            }
            catch (\RuntimeException $exception) {
                if(!$this->hasFallback) 
                    throw $exception;
    
                $sourced = true;
                $value = $this->fallback;
            }
    
            return [$sourced, $value];
        }
    }
}

?>