<?php declare(strict_types = 1);

namespace Slate\Foundation\Console\Command\FileSystem {

    use Slate\Exception\IOException;
    use Slate\Foundation\Console\Command;

    class LinkCommand extends Command {
        public const NAME = "link";

        public function handle(bool $fresh = false): void {
            $links = env("links");
            $root  = env("mvc.path.absolute.root");
            
            foreach($links as $source => $destinations) {
                \Path::assertDirExists($source);

                $destinations = \Arr::associate(\Arr::always($destinations), true);

                foreach($destinations as $destination => $flag) {
                    $destination = $root.\Path::normalise($destination);
                    
                    if($flag ? \Path::exists($destination) : false) {
                        if($fresh) {
                            if(!unlink($destination))
                                throw new IOException([$destination], IOException::ERROR_DELETION_FAILURE);
                        }
                        else {
                            $flag = false;
                        }
                    }

                    if($flag) {
                        \Path::assertNotFileExists($destination);

                        if(\Path::isDir($source)) {
                            $code = null;

                            if((exec("ln -s $source $destination", result_code: $code) === false) && $code != 0) {
                                debug(error_get_last());
                                throw new IOException([$source, $destination], IOException::ERROR_LINK_FAILURE);
                            }
                        }
                        else if(!link($source, $destination)) {
                            debug(error_get_last());
                            throw new IOException([$source, $destination], IOException::ERROR_LINK_FAILURE);
                        }

                        debug("$source => $destination");
                    }
                }
            }
        }
    }
}

?>