<?php declare(strict_types = 1);

namespace Slate\Sql\Statement {

    use Generator;
    use Slate\Facade\DB;
    use Slate\Facade\App;

    trait TSqlSelectStatementGet {
        public function get() {
            $query = $this->toSql();
            $conn = $this->conn();

            foreach($conn->soloquery($query) as $row) {
                yield $row;
            }
        }

        public function all() {
            return iterator_to_array($this->get());
        }

        /**
         * A function that enables nesting joins through the use of trackers.
         *
         * @param array $relations
         *
         * @return array<string,array>
         */
        public function nested(array $relations): array {
            $instances = [];
            $keyValues  = array_flip(\Arr::keys($relations));

            \Arr::mapValues($keyValues, fn(): mixed => null);
            \Arr::mapValues($relations, fn(string|array $keyNames): array => \Arr::always($keyNames));

            foreach($this->get() as $row) {
                $refs = [&$instances];

                foreach($relations as $tableName => $keyNames) {
                    $ref  = &$refs[array_key_last($refs)];
                    array_pop($refs);
                    $keyValue = \Arr::join(\Arr::filter(\Arr::map($keyNames, fn(string $keyName): mixed => $row[$keyName])), ":");

                    $keyValues[$tableName] = $keyValue;


                    if($keyValue !== "") {
                        if(!\Arr::hasKey($ref, $keyValue)) {
                            $subrow = \Arr::filter(
                                $row,
                                function(string $columnName) use($tableName) {
                                    return \Str::startswith($columnName, "$tableName.");
                                },
                                \Arr::FILTER_KEY
                            );
    
                            \Arr::mapKeys(
                                $subrow,
                                fn(mixed $value, string $columnName): string => \Str::removePrefix($columnName, "$tableName.")
                            );
    
                            $ref[$keyValue] = [
                                ...$subrow,
                                "children" => []
                            ];
                        }

                        $refs[] = &$ref[$keyValue]["children"];
                        
                    }
                    
                }
            }

            return $instances;
        }
    }
}

?>