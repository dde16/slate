<?php

namespace Slate\Exception {

use SplFileInfo;
    use Throwable;

class IOException extends SlateException {
        public const ERROR_DIR_NOT_FOUND     = (1<<0);
        public const ERROR_FILE_NOT_FOUND    = (1<<1);
        public const ERROR_PATH_NOT_FOUND    = 
            IOException::ERROR_DIR_NOT_FOUND
            | IOException::ERROR_FILE_NOT_FOUND;
        
        public const ERROR_DIR_OPEN_FAILURE  = (1<<2);
        public const ERROR_FILE_OPEN_FAILURE = (1<<3);
        public const ERROR_PATH_OPEN_FAILURE = 
            IOException::ERROR_DIR_OPEN_FAILURE
            | IOException::ERROR_FILE_OPEN_FAILURE;

        public const ERROR_FILE_IS_DIR_MISMATCH = (1<<4);
        public const ERROR_DIR_IS_FILE_MISMATCH = (1<<5);

        public const ERROR_UNRESOLVABLE_PATH    = (1<<6);
        public const ERROR_UNSAFE_PATH          = (1<<7);

        public const ERROR_LOCK_FAILURE         = (1<<8);
        public const ERROR_LOCK_CONFLICT        = (1<<9);

        public const ERROR_UNLOCK_FAILURE       = (1<<9);

        public const ERROR_RENAME_FAILURE       = (1<<10);
        public const ERROR_COPY_FAILURE         = (1<<11);

        public const ERROR_FILE_OPEN_ASSERTION  = (1<<12);
        public const ERROR_DIR_OPEN_ASSERTION   = (1<<13);

        public const ERROR_MESSAGES       = [
            IOException::ERROR_DEFAULT                => "Unknown IO error while accessing '{path}'.",
            IOException::ERROR_DIR_NOT_FOUND          => "Directory at '{path}' doesn't exist.",
            IOException::ERROR_FILE_NOT_FOUND         => "File at '{path}' doesn't exist.",
            IOException::ERROR_PATH_NOT_FOUND         => "Path '{path}' doesn't exist.",

            IOException::ERROR_DIR_OPEN_FAILURE       => "Unable to open directory at '{path}'.",
            IOException::ERROR_FILE_OPEN_FAILURE      => "Unable to open file at '{path}'.",
            IOException::ERROR_PATH_OPEN_FAILURE      => "Unable to path '{path}'.",

            IOException::ERROR_FILE_IS_DIR_MISMATCH   => "Expecting file, got directory '{path}'.",
            IOException::ERROR_DIR_IS_FILE_MISMATCH   => "Expecting directory, got file '{path}'.",

            IOException::ERROR_UNRESOLVABLE_PATH      => "Unable to resolve path '{path}'.",

            IOException::ERROR_UNSAFE_PATH            => "Sub path '{subPath}' is not a child of root path '{rootPath}'.",

            IOException::ERROR_LOCK_FAILURE           => "Unable to lock file '{path}'.",
            IOException::ERROR_LOCK_CONFLICT          => "Unable to lock file '{path}' as it is locked by another process.",
            IOException::ERROR_UNLOCK_FAILURE         => "Unable to unlock file '{path}'.",

            IOException::ERROR_RENAME_FAILURE         => "Unable to rename file from '{from}' to '{to}'.",
            IOException::ERROR_COPY_FAILURE           => "Unable to copy file from '{from}' to '{to}'.",

            IOException::ERROR_FILE_OPEN_ASSERTION    => "File '{path}' has not yet been opened or has closed prematurely.",
            IOException::ERROR_DIR_OPEN_ASSERTION     => "Directory '{path}' has not yet been opened or has closed prematurely.",
        ];
        public function __construct(string|array $argument = null, int $code = 0, ?Throwable $previous = null) {
            if(is_array($argument))
                $argument = \Arr::map($argument, fn($v) => $v instanceof SplFileInfo ? $v->__toString() : $v);

            parent::__construct($argument, $code, $previous);
        }
    }
}


?>