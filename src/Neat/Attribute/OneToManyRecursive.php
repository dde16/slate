<?php declare(strict_types = 1);

namespace Slate\Neat\Attribute {
    use Attribute;

    #[Attribute(Attribute::TARGET_PROPERTY)]
    class OneToManyRecursive extends OneToAnyRecursive {
        public function __construct(string $firstProperty, ?string $secondProperty = null) {
            parent::__construct($firstProperty, $secondProperty);

            $parent = $this->parent;

            if($parent->hasType()) {
                $parentType = $parent->getType();
                $parentTypeName = $parentType->getName();
                
                if($parentTypeName !== "array")
                    throw new \Error(\Str::format(
                        "Property {}::\${} with a OneToManyRecursive defined must be of type 'array'.",
                        $parent->getDeclaringClass()->getName(),
                        $parent->getName()
                    ));
            }
        }
    }
}

?>