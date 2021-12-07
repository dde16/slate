<?php

namespace Slate\Metalang {

    use Slate\Metalang\Attribute\HookCall;
    use Slate\Metalang\Attribute\HookGet as HookGetImplementor;
    use Slate\Metalang\Attribute\HookSet as HookSetImplementor;
    use Slate\Metalang\Attribute\HookCall as HookCallImplementor;
    use Slate\Metalang\Attribute\HookCallStatic as HookCallStaticImplementor;

    abstract class MetalangClass {
        use TMetalangClass;

        public const DESIGN = MetalangDesign::class;

        public function __construct() {
            if(\Cls::getConstant(static::class, "JIT", false) === false)
                static::design();
        }
    }
}

?>