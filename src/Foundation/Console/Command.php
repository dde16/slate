<?php declare(strict_types = 1);

namespace Slate\Foundation\Console {

    use RuntimeException;
    use Slate\Data\Table;
    use Slate\Foundation\Console;

    class Command {
        public const NAME = NULL;
        public const ARGUMENTS = [];

        private ?ArgumentParser $parser = null;

        public array $options = [];

        public Console $app;

        public function __construct(Console $app) {
            if (static::NAME === NULL)
                throw new RuntimeException("Command " . static::class . " doesn't have a name.");

            $this->app = $app;
        }

        public function getParser(): ArgumentParser {
            if ($this->parser === null)
                $this->parser = ArgumentParser::fromArray(static::ARGUMENTS);

            return $this->parser;
        }
    }
}

?>