<?php

namespace Slate\Mvc\Attribute {
    use Attribute;
    use Slate\Http\HttpRequest;

    #[Attribute(Attribute::TARGET_METHOD)]
    class Preprocessor extends Processor {}
}

?>