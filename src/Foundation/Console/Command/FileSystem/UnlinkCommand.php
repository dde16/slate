<?php declare(strict_types = 1);

namespace Slate\Foundation\Console\Command\FileSystem {

    use Slate\Exception\IOException;
    use Slate\Foundation\Console\Command;

    class UnlinkCommand extends Command {
        public const NAME = "unlink";

        public function handle(bool $fresh = false): void {
            $links = env("links");
            $root  = env("mvc.path.absolute.root");
            
            foreach($links as $source => $destinations) {
                \Path::assertDirExists($source);

                $destinations = \Arr::associate(\Arr::always($destinations), true);

                foreach($destinations as $destination => $flag) {
                    $destination = $root.\Path::normalise($destination);

                    if(\Path::exists($destination)) {
                        if(!unlink($destination))
                            throw new IOException([$destination], IOException::ERROR_DELETION_FAILURE);
                    }
                    
                    debug("unlinked $destination");
                }
            }
        }
    }
}

?>