<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Slate\Data\Collection;

final class CollectionTest extends TestCase {
    public function test_CollectionPassThruFunctionsExist(): void {
        $this->assertEquals(
            [],
            \Arr::filter(
                Collection::SPL_FUNCTIONS_RETURN,
                fn(string|array $callable): bool => !is_callable($callable)
            )
        );

        $this->assertEquals(
            [],
            \Arr::filter(
                Collection::SPL_FUNCTIONS_SET,
                fn(string|array $callable): bool => !is_callable($callable)
            )
        );
        
    }
}

?>