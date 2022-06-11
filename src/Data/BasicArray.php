<?php declare(strict_types = 1);

namespace Slate\Data {
    use ArrayAccess;
    use Countable;
    use Slate\Data\Contract\IArrayAccess;
    use Slate\Data\Contract\IArrayBackwardConvertable;
    use Slate\Data\Contract\IArrayForwardConvertable;
    use Slate\Data\Iterator\IExtendedIterator;

    /**
     * A base for classes such as the Collection to be used as an easy way
     * to make a class accessible as an array.
     */
    class BasicArray implements IArrayAccess, IExtendedIterator, Countable, IArrayForwardConvertable {
        public const CONTAINER = "items";

        use TBasicArray;

        protected array $items;

        public function __construct(array $items = []) {
            $this->fromArray($items);
        }

        public function fromArray(array $array): void {
            $this->{static::CONTAINER} = $array;
        }
    }
}

?>