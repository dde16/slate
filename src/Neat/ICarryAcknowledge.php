<?php

namespace Slate\Neat {
    use Slate\Neat\Attribute\Carry as CarryAttribute;

interface ICarryAcknowledge {
        function acknowledgeCarry(object $carry): void;
    }
}

?>