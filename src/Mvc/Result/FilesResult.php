<?php

namespace Slate\Mvc\Result {

    use Slate\Http\HttpResponse;
    use Slate\IO\Mime;

    use Slate\Http\HttpResponseFile;

    use Slate\Mvc\Env;

    class FilesResult extends CommandResult {
        protected array $files;

        public function __construct(array $files, bool $bypass = true) {
            $this->files = \Arr::mapAssoc(
                $files,
                function($field, $file) {
                    return [
                        $field,
                        \Any::isString($file)
                            ? new HttpResponseFile(basename($file), $file)
                            : $file
                    ];
                }
            );
            parent::__construct($bypass);
        }
        
        public function modify(HttpResponse &$response): void {
            foreach($this->files as $name => $file)
                $response->files[$name] = $file;
        }
    }
}

?>