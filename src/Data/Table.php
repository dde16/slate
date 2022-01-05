<?php

namespace Slate\Data {

    use Generator;
    use Slate\Data\Iterator\ArrayAssociatingIterator;

    /**
     * A class to pretty print rows.
     */
    class Table extends BasicArray {
        public const CONTAINER = "rows";

        public array $columns;
        public array $rows;
        public array $chars;

        public function __construct(array $columns = []) {
            $this->columns = $columns;
            $this->rows    = [];
            $this->chars   = [
                "top.start.junction"         => "┌",
                "top.middle.junction"        => "┬",
                "top.end.junction"           => "┐",
                
                "bottom.start.junction"      => "└",
                "bottom.middle.junction"     => "┴",
                "bottom.end.junction"        => "┘",

                "surrounded.start.junction"  => "├",
                "surrounded.middle.junction" => "┼",
                "surrounded.end.junction"    => "┤",
                
                "joiner.horizontal"          => "─",
                "joiner.vertical"            => "│"
            ];

            if(!\Arr::all($columns, fn(mixed $value): bool => is_string($value)))
                throw new \Error("Columns must be strings.");
        }

        public function assoc(): ArrayAssociatingIterator {
            return (new ArrayAssociatingIterator($this->columns, $this->rows));
        }

        public function getLabel(int $index, array $row): int {
            return $index;
        }

        public function resolveColumn(int|string $column): string {
            if(is_string($column)) {
                $column = \Arr::find($this->columns, $column);

                if($column === -1)
                    throw new \Error("Column '{$column}' doesn't exist.");
            }

            if($column > count($this->columns)-1)
                throw new \Error("Column index '{$column}' is out of the range of available columns.");

            return $column;
        }

        public function getColumn(int|string $column, int $group = null): array {
            $column = $this->resolveColumn($column);

            $values = \Arr::map(
                $this->rows,
                function($row) use($column) {
                    return $row[$column];
                }
            );

            if($group)
                $values = \Arr::chunk($values, $group);
            
            return $values;
        }

        // public function movingAverage(int|string $sourceColumn, array $lookers): array {
        //     if($size < 2)
        //         throw new \Error("Moving average sizes must be greater than 1.");

        //     list($backwards, $forwards) = $lookers;

        //     $neighbourhoods = \Arr::map(
        //         \Arr::neighbours($this->getColumn($sourceColumn), [$backwards, $forwards]),
        //         function($neighbourhood) use($size) {
        //             $total = array_sum($neighbourhood);
        //             $mean  = $total / $size;

        //             return [$total, $mean];
        //         }
        //     );

        //     return $neighbourhoods;
        // }

        // public function trailingMovingAverage(int|string $sourceColumn, int $size = 2): array {
        //     return $this->movingAverage($sourceColumn, [$size, 0]);
        // }

        // public function centredMovingAverage(int|string $sourceColumn, int $size = 2): array {
        //     $backwards = (int)ceil(($size-1) / 2);
        //     $forwards  = $size - ($backwards + 1);

        //     return $this->movingAverage($sourceColumn, [$backwards, $forwards]);
        // }

        protected function getTableSeparatorString(int $pos, array $sizes, array $chars, array $padsize, bool $delimit): string {
            switch($pos) {
                case -1:
                    list($startJunctionKey, $middleJunctionKey, $endJunctionKey) = [
                        "top.start.junction",
                        "top.middle.junction",
                        "top.end.junction",
                    ];
                    break;
                case 0:
                    list($startJunctionKey, $middleJunctionKey, $endJunctionKey) = [
                        "surrounded.start.junction",
                        "surrounded.middle.junction",
                        "surrounded.end.junction",
                    ];
                    break;
                case 1:
                    list($startJunctionKey, $middleJunctionKey, $endJunctionKey) = [
                        "bottom.start.junction",
                        "bottom.middle.junction",
                        "bottom.end.junction",
                    ];
                    break;
            }

            $startJunction = $chars[$startJunctionKey];
            $middleJunction = $chars[$middleJunctionKey];
            $endJunction = $chars[$endJunctionKey];

            return
                ($delimit ? $startJunction : "").
                \Arr::join(
                    \Arr::map(
                        $sizes,
                        function($size) use($chars,$padsize) {
                            return \Arr::join(
                                array_fill(
                                    0,
                                    $size + (
                                        $padsize["right"] ?: $padsize["r"] ?: 0
                                    ) + (
                                        $padsize["left"] ?: $padsize["l"] ?: 0
                                    ),
                                    $chars["joiner.horizontal"]
                                )
                            );
                        }
                    ),
                    $delimit ? $middleJunction : ""
                ).
                ($delimit ? $endJunction : "")
            ;
        }

        protected function getTableRowString(array $row, array $sizes, array $chars, array $padsize, array $padwith, bool $delimit): string {
            $row = \Arr::map($row, fn(mixed $value): string => (is_scalar($value) ? \Str::val($value) : \Any::getType($value)));

            $row = \Arr::mapAssoc(
                $row,
                function(int $index, $cell) use($sizes, $padsize, $padwith) {
                    return [
                        $index,
                        \Str::padLeft(
                            \Str::padRight(
                                $cell,
                                $padwith["x"],
                                ($sizes[$index] ?: 0)  + ($padsize["right"] ?: $padsize["r"] ?: 0)
                            ), $padwith["x"], $sizes[$index] + ($padsize["left"] ?: $padsize["l"] ?: 0) + ($padsize["right"] ?: $padsize["r"] ?: 0)
                        )
                    ];
                }
            );

            $row = \Arr::join($row, ($delimit ? $chars["joiner.vertical"] : ""));


            return $delimit ? \Str::wrap($row, $chars["joiner.vertical"]) : $row;
        }

        public function toTableString(int $from = 0, int $to = null, array $chars = null, array $padsize = null, array $padwith = null, bool $delimitColumns = null, bool $delimitRows = null): string {
            return \Arr::join(
                iterator_to_array(
                    $this->toTableIterator($from, $to, $chars, $padsize, $padwith, $delimitColumns, $delimitRows)
                ),
                "\n"
            );
        }

        public function fromAssoc(array $rows): void {
            foreach($rows as $row) {
                $rowFiltered = [];

                foreach($this->columns as $column)
                    $rowFiltered[] = $row[$column];

                $this->rows[] = $rowFiltered;
            }
        }

        public function toTableIterator(
            int $from = 0,
            int $to = null,
            array $chars = null,
            array $padsize = null,
            array $padwith = null,
            bool $delimitColumns = null,
            bool $delimitRows = null
        ): Generator {
            $delimitColumns = $delimitColumns ?? true;
            $delimitRows = $delimitRows ?? true;
            $padsize = $padsize ?? [
                "top"    => 1,
                "left"   => $delimitRows ? 1 : 0,
                "bottom" => 1,
                "right"  => 1
            ];

            $chars = array_merge($this->chars, $chars ?? []);

            $padwith = $padwith ?? ["y" => "\n", "x" => " "];

            $columnsCount = count($this->columns);
            $rowsCount = count($this->rows);

            if($to === null)
                $to = $rowsCount;
            else if($to > $rowsCount)
                throw new \Error();

            $columnSizes = \Arr::map(
                $this->columns,
                function($column) {
                    return strlen($column);
                }
            );

            for($rowIndex = $from; $rowIndex < $to; $rowIndex++) {
                if(($row = $this->rows[$rowIndex]) !== null) {
                    $row = \Arr::map($row, fn($value) => is_scalar($value) ? \Str::val($value) : \Any::getType($value));
    
                    for($columnIndex = 0; $columnIndex < $columnsCount; $columnIndex++) {
                        $cell = $row[$columnIndex];
                        $cellSize = strlen($cell);
    
                        if($cellSize > $columnSizes[$columnIndex]) {
                            $columnSizes[$columnIndex] = $cellSize;
                        }
                    }
                }
            }

            $middleSeparator = $this->getTableSeparatorString(0, $columnSizes, $chars, $padsize, $delimitColumns);

            if($delimitRows) {
                $startSeparator = $this->getTableSeparatorString(-1, $columnSizes, $chars, $padsize, $delimitColumns);
                yield $startSeparator;
            }

            if(!\Arr::isEmpty($this->columns))
                yield $this->getTableRowString($this->columns, $columnSizes, $chars, $padsize, $padwith, $delimitColumns);

            foreach($this->rows as $row){ 
                if($delimitRows)
                    yield $middleSeparator;
                
                yield $this->getTableRowString($row, $columnSizes, $chars, $padsize, $padwith, $delimitColumns);
            }
            
            if($delimitRows)
                yield $this->getTableSeparatorString(1, $columnSizes, $chars, $padsize, $delimitColumns);
        }
    }
}

?>