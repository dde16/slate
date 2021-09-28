<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Slate\Data\BasicArray;

final class ArrTest extends TestCase {
    public function testCanParsePrefixedList(): void {
        $this->assertEquals(
            [
                "key0" => "value0",
                "key1" => "value1"
            ],
            \Arr::fromList(
                [
                    "key.0" => "key0", "value.0" => "value0",
                    "key.1" => "key1", "value.1" => "value1"
                ],
                "key.",
                "value."
            )
        );
    }

    public function testCanDescribeArrayPositionals(): void {
        $actual = iterator_to_array(\Arr::describe([1, 2, 3]));

        $this->assertEquals(
            [
                [ \Arr::POS_START,  1 ],
                [ \Arr::POS_MIDDLE, 2 ],
                [ \Arr::POS_END,    3 ]
            ],
            $actual
        );
    }

    public function testCanConvertArrayToStringList(): void {
        $this->assertEquals(
            "(`5`, `4`, `3`, `2`, `1`)",
            \Arr::list(
                [5,4,3,2,1],
                ", ",
                "``",
                "()"
            )
        );
    }

    public function testCanBranchArray(): void {
        $this->assertEquals(
            [
                [["a", 0], "value"],
                [["a", "b", "c"], null],
                [["a", "b", "d"], [1,2,3]]
            ],
            \Arr::branches([
                "a" => [
                    "value",
                    "b" => [
                        "c" => null,
                        "d" => [
                            1, 2, 3
                        ]
                    ]
                ]
            ])
        );

        $this->assertEquals(
            [
                [["a", 0], "value"],
                [["a", "b", "c"], null],
                [["a", "b", "d", 0], 1]
            ],
            \Arr::branches([
                "a" => [
                    "value",
                    "b" => [
                        "c" => null,
                        "d" => [
                            1
                        ]
                    ]
                ]
            ], \Arr::DOTS_EVAL_ALL)
        );
    }

    public function testCanThreshold(): void {
        $actual = \Arr::threshold(
            [1,23,25,50,10,75],
            50
        );

        $this->assertContains(50, $actual);
        $this->assertContains(75, $actual);
    }

    public function testCanDetermineDepthOfArray(): void {
        $this->assertEquals(
            3,
            \Arr::depthOf([
                0 => [
                    1 => [
                        2 => null
                    ]
                ]
            ])
        );
    }

    public function testCanCheckValidOffset(): void {
        $this->assertTrue(\Arr::isValidOffset(1));
        $this->assertTrue(\Arr::isValidOffset("x"));
        $this->assertFalse(\Arr::isValidOffset([]));
        $this->assertFalse(\Arr::isValidOffset(new stdClass));
        $this->assertFalse(\Arr::isValidOffset(1.0));
        $this->assertFalse(\Arr::isValidOffset(false));
        $this->assertFalse(\Arr::isValidOffset(true));
    }

    public function testCanCheckValidAssocOffset(): void {
        $this->assertTrue(\Arr::isAssocOffset("x"));
        $this->assertFalse(\Arr::isAssocOffset("1"));
        $this->assertFalse(\Arr::isAssocOffset(1));

        $this->assertFalse(\Arr::isAssocOffset([]));
        $this->assertFalse(\Arr::isAssocOffset(new stdClass));
        $this->assertFalse(\Arr::isAssocOffset(1.0));
        $this->assertFalse(\Arr::isAssocOffset(false));
        $this->assertFalse(\Arr::isAssocOffset(true));
    }

    public function testCanCheckArrayStartsWith(): void {
        $this->assertTrue(\Arr::startswith([1,2,3], [1,2]));
        $this->assertFalse(\Arr::startswith([1,2,3], [2,3]));
    }

    public function testCanCheckAllOfArray(): void {
        $this->assertTrue(\Arr::all([1,2,3,4], fn($v) => is_int($v)));
        $this->assertFalse(\Arr::all([1,"2",3,4], fn($v) => is_int($v)));
    }

    public function testCanCheckAnyOfArray(): void {
        $this->assertTrue(\Arr::any([1,"2",3,4], fn($v) => is_string($v)));
        $this->assertFalse(\Arr::any([1,"2",3,4], fn($v) => is_bool($v)));
    }

    public function testCanCheckArrayAccessibility(): void {
        $testClass = BasicArray::class;
        $testObject = new BasicArray;

        $this->assertTrue(\Arr::isAccessible($testClass));
        $this->assertTrue(\Arr::isAccessible($testObject));

        $this->assertFalse(\Arr::isAccessible("asdasdasd"));
        $this->assertFalse(\Arr::isAccessible(new stdClass));
        $this->assertFalse(\Arr::isAccessible(1.0));
        $this->assertFalse(\Arr::isAccessible(false));
        $this->assertFalse(\Arr::isAccessible(true));
    }

    public function testCanTestIfArrayIsAssociative(): void {
        $this->assertTrue(
            \Arr::isAssoc(
                [
                    1, "two" => 2, 3
                ],
                false
            )
        );

        $this->assertFalse(
            \Arr::isAssoc(
                [ 1, "2" => 2, 3 ],
                false
            )
        );

        $this->assertTrue(
            \Arr::isAssoc(
                [ 1, "2" => 2, 3 ],
                true
            )
        );

        $this->assertTrue(
            \Arr::isAssoc(
                [ 1, "3" => 2, 3 ],
                true
            )
        );
    }

    public function testCanTestIfArrayIsEmpty(): void {
        $this->assertTrue(\Arr::isEmpty([]));
        $this->assertFalse(\Arr::isEmpty([1,2,3]));
    }

    public function testArrayDotsByValue(): void {
        $this->assertEquals(
            [
                "a.0" => "value",
                "a.b.c" => null,
                "a.b.d" => [1,2,3]
            ],
            \Arr::dotsByValue([
                "a" => [
                    "value",
                    "b" => [
                        "c" => null,
                        "d" => [
                            1, 2, 3
                        ]
                    ]
                ]
            ], ".", \Arr::DOTS_EVAL_ASSOC)
        );

        $this->assertEquals(
            [
                "a.0" => "value",
                "a.b.c" => null,
                "a.b.d.0" => 1
            ],
            \Arr::dotsByValue([
                "a" => [
                    "value",
                    "b" => [
                        "c" => null,
                        "d" => [
                            1
                        ]
                    ]
                ]
            ], ".", \Arr::DOTS_EVAL_ALL)
        );
    }

    public function testArrayEnd(): void {
        $test = [1,2,3,4,6];

        $this->assertEquals(
            \Arr::endEntry($test),
            [4, 6]
        );

        $this->assertEquals(
            \Arr::end($test),
            6
        );

        $this->assertEquals(
            \Arr::endEntry($test, fn($v) => $v < 3),
            [1, 2]
        );

        $this->assertEquals(
            \Arr::end($test, fn($v) => $v < 3),
            2
        );

        $this->assertEquals(
            \Arr::lastEntry($test),
            [4, 6]
        );

        $this->assertEquals(
            \Arr::last($test),
            6
        );

        $this->assertEquals(
            \Arr::lastEntry($test, fn($v) => $v < 3),
            [1, 2]
        );

        $this->assertEquals(
            \Arr::last($test, fn($v) => $v < 3),
            2
        );
    }

    public function testArrayMissing(): void {
        $test = [
            "this" => 1,
            "is" => 2,
            "present" => 3
        ];

        $this->assertEquals(
            [
                "this" => 1,
                "is" => 2,
                "not" => 4,
                "another" => 4,
                "present" => 3
            ],
            \Arr::missing($test, ["not", "another"], fn($k) => 4)
        );
    }

    public function testArrayFindAllElements(): void {
        $this->assertEquals(
            [1, 2],
            \Arr::findAll(
                [0,1,2],
                true
            )
        );

        $this->assertEquals(
            [2],
            \Arr::findAll(
                [0,1,2],
                2,
                true
            )
        );
    }
    
    public function testArrayHasKeys(): void {
        $this->assertTrue(
            \Arr::hasKeys(
                [1,2,3],
                [0,2]
            )
        );

        $this->assertTrue(
            \Arr::hasKeys(
                ["one" => 1, "two" => 2,3],
                ["one"]
            )
        );

        $this->assertFalse(
            \Arr::hasKeys(
                ["one" => 1, "two" => 2,3],
                ["three"]
            )
        );
    }

    public function testArrayStart(): void {
        $test = [1,2,3,4,6];

        $this->assertEquals(
            \Arr::startEntry($test),
            [0, 1]
        );

        $this->assertEquals(
            \Arr::start($test),
            1
        );

        $this->assertEquals(
            \Arr::startEntry($test, fn($v) => $v > 3),
            [3, 4]
        );

        $this->assertEquals(
            \Arr::start($test, fn($v) => $v > 3),
            4
        );

        $this->assertEquals(
            \Arr::firstEntry($test),
            [0, 1]
        );

        $this->assertEquals(
            \Arr::first($test),
            1
        );

        $this->assertEquals(
            \Arr::firstEntry($test, fn($v) => $v > 3),
            [3, 4]
        );

        $this->assertEquals(
            \Arr::first($test, fn($v) => $v > 3),
            4
        );
    }
}

?>