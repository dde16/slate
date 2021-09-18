<?php

namespace Slate\Mvc\Attribute {
    use Attribute;

    use Slate\Http\HttpResponse;
    use Slate\Http\HttpRequest;

    #[Attribute(Attribute::TARGET_METHOD)]
    class Postprocessor extends Processor {}
}

?>