<?php

namespace Slate\Neat\Implementation {

    use Slate\Exception\ParseException;
    use Slate\Neat\Attribute\Alias;
    use Slate\Neat\Attribute\Json as JsonAttribute;
    use Slate\Neat\Model;

    trait TJsonAttributeImplementation {
        #[Alias("fromJson")]
        public static function staticFromJson(string|array|object $json): static {
            $object = new static();
            $object->objectFromJson($json);

            return $object;
        }

        #[Alias("fromJson")]
        public function objectFromJson(string|array|object $json): void {
            $design = static::design();

            if(is_string($json))
                $json = json_decode($json, true);

            foreach($design->getAttrInstances(JsonAttribute::class) as $attribute) {
                $property = $attribute->parent;
                $propertyType = $property->hasType() ? $property->getType() : null;
                $value = \Compound::get($json, $attribute->getPath());

                if($value === null && (
                    $propertyType !== null
                        ? !$propertyType->allowsNull()
                        : false
                )) {
                    throw new ParseException(\Str::format(
                        "'{}' is null despite not allowing it.",
                        $attribute->getPath()
                    ));
                }

                if($propertyType) {
                    $propertyTypeName = $propertyType->getName();

                    if(($propertyTypeClass = \Type::getByName($propertyTypeName)) === null) {
                        throw new \Error();
                    }
                }

                if(($instantiate = $attribute->getInstantiateClass()) !== null) {
                    if(!is_subclass_of($instantiate, Model::class))
                        throw new \Error("Json marshal class '{}' isnt json deserialisable.");

                    if(is_array($value)) {
                        if($propertyTypeClass === \Arr::class) {
                            $hold = [];
                            foreach($value as $key => $item) {
                                if(\Any::isCompound($item)) {

                                    $instance = new $instantiate();
                                    $instance->fromJson($item);
                                    $hold[$key] = $instance;
                                }

                            }

                            $value = $hold;
                        }
                        else {
                            $instance = new $instantiate();
                            $instance->fromJson($value);

                            $value = $instance;
                        }
                    }

                }
                else if($propertyTypeClass ? !$propertyType->allowsNull() : false) {
                    if(!$propertyTypeClass::validate($value)) {
                        if(($value = $propertyTypeClass::parse($value)) === null) {
                            throw new \Error("Unable to validate value '" . $attribute->getPath() . "'.");
                        }
                    }
                }
                

                $this->{$property->getName()} = $value;
            }
        }

    }
}

?>