<?php

namespace Slate\IO {

    use Closure;
    use Error;
    use Slate\Utility\Facade;

    /**
     * Facade for buffer functions.
     * 
     * TODO: refractor
     */
    class Buffer extends Facade {
        const START = 1;
        const PIPE  = 2;
        const CLEAN = 4;
        const FLUSH = 8;
        const END   = 16;

        /**
         * Import Standard Php Library constants
         */
        const HANDLER_START     = PHP_OUTPUT_HANDLER_START;
        const HANDLER_WRITE     = PHP_OUTPUT_HANDLER_WRITE;
        const HANDLER_FLUSH     = PHP_OUTPUT_HANDLER_FLUSH;
        const HANDLER_CLEAN     = PHP_OUTPUT_HANDLER_CLEAN;
        const HANDLER_FINAL     = PHP_OUTPUT_HANDLER_FINAL;
        const HANDLER_CONT      = PHP_OUTPUT_HANDLER_CONT;
        const HANDLER_END       = PHP_OUTPUT_HANDLER_END;
        const HANDLER_CLEANABLE = PHP_OUTPUT_HANDLER_CLEANABLE;
        const HANDLER_FLUSHABLE = PHP_OUTPUT_HANDLER_FLUSHABLE;
        const HANDLER_REMOVABLE = PHP_OUTPUT_HANDLER_REMOVABLE;
        const HANDLER_STANDARD  = PHP_OUTPUT_HANDLER_STDFLAGS;

        /**
         * Starts a buffer using spl 'ob_start'.
         * 
         * @param Closure  $callback The optional callback function called when the buffer ended.
         * @param int      $size     The maximum size of the buffer
         * @param int      $flags    Any additional flags defined as Buffer::CONSTANT
         * 
         * @return void
         */
        public static function start(Closure $callback = null, int $size = 0, int $flags = Buffer::HANDLER_STANDARD) {
            if(ob_start($callback, $size, $flags) === FALSE)
                throw new Error("Unknown error while creating buffer.");
        }

        /**
         * Gets contents of the current active buffer.
         * 
         * @return string|bool String if successful, false otherwise.
         */
        public static function pipe() {
            return ob_get_contents();
        }

        /**
         * Cleans contents of the current active buffer.
         * 
         * @return bool Whether the buffer clearances was successful
         */
        public static function clean() {
            return ob_clean();
        }

        /**
         * Ends the current buffer.
         * 
         * @param int $flags Applicable flags; Buffer::PIPE, Buffer::CLEAN and Buffer::FLUSH.
         * 
         * @return void|string Null/void if the Buffer::PIPE constant was not provided, otherwise string (buffer contents).
         */
        public static function end(int $flags = 0) {
            $data = NULL;

            if($flags & Buffer::PIPE) {
                $data = ob_get_contents();
            }

            $ender = null;
            if($flags & Buffer::FLUSH) {
                $ender = 'ob_end_flush';
            }
            else {
                $ender = 'ob_end_clean';
            }

            $status = (Closure::fromCallable($ender))();

            if($status === false)
                throw new Error("Unknown error while calling {$ender}.");

            return $data;
        }

        /**
         * Will send the contents of the buffer.
         * 
         * @return bool
         */
        public static function flush() {
            return ob_flush();
        }

        /**
         * Gets the output of a given function.
         * 
         * @param Closure $function The function to call and get the output of.
         * @param Closure $callback @see Buffer::start()
         * 
         * @return string
         */
        public static function wrap(Closure $function, Closure $callback = null) {
            Buffer::start($callback);
            
            \Fnc::call($function);
            $data = Buffer::end(Buffer::PIPE | Buffer::CLEAN);

            return $data;
        }
    }
}

?>