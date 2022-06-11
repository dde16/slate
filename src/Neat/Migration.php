<?php

namespace Slate\Neat {
    abstract class Migration {
        public abstract function up(): void;
        public abstract function down(): void;
    }
}

?>