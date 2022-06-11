<?php declare(strict_types = 1);

namespace Slate\Neat {
    use Slate\Neat\Attribute\Carry as CarryAttribute;

interface ICarryAcknowledge {
        function acknowledgeCarry(object $carry): void;
    }
}

?>