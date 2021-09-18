<?php

abstract class Rsc {
    public static function getType($resource): string {
        return get_resource_type($resource);
    }
}

?>