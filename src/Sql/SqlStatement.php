<?php

namespace Slate\Sql {

use Slate\Data\IStringForwardConvertable;

abstract class SqlStatement extends SqlConstruct {
        public array $variables = [];
        protected ?string $conn = null;

        public function using(string $conn): static {
            $this->conn = $conn;

            return $this;
        }

        public function var(string $name, string|IStringForwardConvertable $value = "NULL"): static {
            $this->variables[$name] = $value;

            return $this;
        }

        public function toString(): string {
            return 
                \Arr::join(
                    [
                        ...\Arr::values(\Arr::mapAssoc(
                            $this->variables,
                            fn($name, $value) => [$name, "SET @$name = " . (is_object($value) ? $value->toString() : $value) . ";"]
                        )),
                        parent::toString()
                    ],
                    "\n"
                );
        }
    }
}

?>