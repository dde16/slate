<?php declare(strict_types = 1);

namespace Slate\Neat\Attribute {
    use Slate\Metalang\MetalangAttribute;

    /**
     * Defines a Recursive Common Table Expression
     * relationship.
     */

    /** @method void __construct(string $foreignProperty) */
    /** @method void __construct(string $localProperty, string $foreignProperty) */
    class OneToAnyRecursive extends MetalangAttribute {
        protected ?string $localProperty;
        protected string $foreignProperty;

        public function __construct(string $firstProperty, ?string $secondProperty = null) {
            if ($secondProperty === null) {
                $secondProperty = $firstProperty;
                $firstProperty = null;
            }

            $this->localProperty = $firstProperty;
            $this->foreignProperty = $secondProperty;
        }

        public function getLocalProperty(): ?string {
            return $this->localProperty;
        }

        public function getForeignProperty(): string {
            return $this->foreignProperty;
        }
    }
}

?>