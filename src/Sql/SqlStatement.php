<?php declare(strict_types = 1);

namespace Slate\Sql {

    use Slate\Data\Contract\IStringForwardConvertable;
    use Slate\Facade\App;
    use Slate\Sql\Trait\TSqlModifierMiddleware;
    use Slate\Sql\Trait\TSqlModifiers;
    use Slate\Sql\Trait\TSqlUsingConnection;

    abstract class SqlStatement extends SqlConstruct {
        use TSqlUsingConnection;
        use TSqlModifiers;
        use TSqlModifierMiddleware;

        public array $variables = [];

        public const MODIFIERS = 0;

        public function __construct(SqlConnection $conn) {
            $this->conn = $conn;
        }

        public function var(string $name, string|IStringForwardConvertable $value = "NULL"): static {
            $this->variables[$name] = $value;

            return $this;
        }

        public function toSql(): string {
            return 
                \Arr::join(
                    [
                        ...\Arr::values(
                            \Arr::mapAssoc(
                                $this->variables,
                                fn($name, $value) => [$name, "SET @$name = " . (is_object($value) ? $value->toString() : $value) . ";"]
                            )
                        ),
                        parent::toSql()
                    ],
                    "\n"
                );
        }

        public function go(): bool {
            return $this->conn()->prepare($this->toSql())->execute();
        }

        
    }
}

?>