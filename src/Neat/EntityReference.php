<?php

namespace Slate\Neat {

    use Slate\Data\IStringForwardConvertable;
    use Slate\Data\TStringNativeForwardConvertable;
    use Slate\Facade\App;

    class EntityReference implements IStringForwardConvertable {
        use TStringNativeForwardConvertable;

        protected string $entity;
        protected ?string $property;
        protected ?string $column;
        protected int $flags;

        public function __construct(string $entity, ?string $property, ?string $column, int $flags = 0) {
            $this->entity   = $entity;
            $this->property = $property;
            $this->column   = $column;
            $this->flags    = $flags;
        }

        public function getEntity(): string {
            return $this->entity;
        }

        public function getProperty(): string {
            return $this->property;
        }

        public function getColumn(): string {
            return $this->column;
        }

        public function toString(int $flags = null): string {
            $flags  = $flags === null ? $this->flags : $flags;
            $affix = $this->column;

            $driver = App::conn(\Cls::getConstant($this->entity, "CONN"));

            if($driver === null)
                throw new \Error("Unable to get the connection for this Entity.");
            
            $driver = $driver::class;
            $wrapper = !\Integer::hasBits($flags, Entity::REF_NO_WRAP) ? $driver::TOKEN_IDENTIFIER_DELIMITER : "";

            if(\Integer::hasBits($flags, Entity::REF_SQL)) {
                $arguments = [$this->entity::SCHEMA, $this->entity::TABLE, $affix];

                if(\Integer::hasBits($flags, Entity::REF_ITEM_WRAP)) {
                    $format = \Arr::join(\Arr::repeat(\Str::wrapc("{}", $wrapper), $affix !== null ? 3 : 2), ".");
                }
                else if(\Integer::hasBits($flags, Entity::REF_OUTER_WRAP)) {
                    $format = $affix !== null ?  \Str::wrapc("{}.{}.{}", $wrapper) : \Str::wrapc("{}.{}", $wrapper);
                }
                else {
                    throw new \Error();
                }
            }
            else {
                $arguments = [\Str::afterLast($this->entity, "\\")];

                if($affix !== null) {
                    if(\Integer::hasBits($flags, Entity::REF_RESOLVED)) {
                        $affix = $this->property;
                    }

                    $arguments[] = $affix;
                    
                    if(\Integer::hasBits($flags, Entity::REF_OUTER_WRAP)) {
                        $format = \Str::wrapc("{}.{}", $wrapper);
                    }
                    else {
                        $format = \Str::wrapc("{}", $wrapper) . "." . \Str::wrapc("{}", $wrapper);
                    }
                }
                else {
                    $format = \Str::wrapc("{}", $wrapper);
                }
            }

            return \Str::format($format, $arguments);
        }
    }
}

?>