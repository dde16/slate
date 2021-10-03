<?php

namespace Slate\Sql {

    use Slate\Data\IStringForwardConvertable;
    use Slate\Mvc\App;

abstract class SqlStatement extends SqlConstruct {
        use TSqlUsingConnection;

        public array $variables = [];

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

        public function go(): bool {
            return $this->conn()->prepare($this->toString())->execute();
        }
    }
}

?>